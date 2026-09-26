<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\RoundOpened;
use App\Models\GameRound;
use App\Models\GameSession;
use App\Models\GameTheme;
use App\Models\GameThemeWinner;
use App\Models\Player;
use App\Models\Question;
use App\Models\TenantGameConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cycle de vie d'un thème d'élimination et de ses manches (côté pilotage).
 *
 * - Création d'un thème (une partie, I-27) avec sa première manche.
 * - Programmation de la manche suivante (réservée aux survivants, EX-32).
 * - Ouverture / fermeture des fenêtres par l'animateur (I-2 / EX-10) :
 *   l'autorisation de répondre est un ÉTAT SERVEUR (INV-8 / R-3).
 * - Fin de partie (I-28 / EX-35) : tous les survivants, ou tirage au sort
 *   serveur de 1, 3 ou 5 gagnants (options scellées), UNE SEULE FOIS.
 *
 * EX-40 partout : aucune question non validée humainement ne peut être
 * programmée sur une manche — contrôle ici, pas seulement en UI Filament.
 *
 * PROVISOIRE — docs/07 §5.
 */
class EliminationThemeService
{
    public function __construct(
        private readonly EliminationSurvivorService $survivors,
    ) {}

    /**
     * Crée un thème (partie d'élimination) et sa première manche depuis une
     * question validée.
     *
     * @throws ValidationException question non validée (EX-40), déjà utilisée,
     *                             ou ne visant pas la manche 1.
     */
    public function createTheme(GameSession $session, string $title, string $population, Question $firstQuestion): GameTheme
    {
        $this->assertQuestionUsable($firstQuestion, 1, 'first_question_id');

        return DB::transaction(function () use ($session, $title, $population, $firstQuestion): GameTheme {
            $theme = GameTheme::query()->create([
                'game_session_id' => $session->id,
                'title' => $title,
                'population' => $population,
                'status' => GameTheme::STATUS_ACTIVE,
            ]);

            // La question quitte la banque : elle est rattachée au thème.
            $firstQuestion->update(['theme_id' => $theme->id]);

            GameRound::query()->create([
                'theme_id' => $theme->id,
                'round_number' => 1,
                'question_id' => $firstQuestion->id,
                'status' => GameRound::STATUS_PENDING,
            ]);

            return $theme;
        });
    }

    /**
     * Programme la manche suivante du thème depuis une question validée.
     *
     * @throws ValidationException thème terminé, manche courante non clôturée,
     *                             question invalide ou mal numérotée.
     */
    public function createNextRound(GameTheme $theme, Question $question): GameRound
    {
        if ($theme->isFinished()) {
            throw ValidationException::withMessages([
                'theme_id' => [__('interflo.pilot.theme_finished')],
            ]);
        }

        $latest = $theme->latestRound;

        // Programmer pendant qu'une fenêtre est ouverte embrouillerait la
        // conduite : on attend la clôture. (Une manche « pending » ne bloque
        // pas la programmation — seule l'OUVERTURE exige la clôture des
        // manches précédentes, voir openRound.)
        if ($latest !== null && $latest->status === GameRound::STATUS_OPEN) {
            throw ValidationException::withMessages([
                'theme_id' => [__('interflo.pilot.round_currently_open')],
            ]);
        }

        $nextNumber = ($latest?->round_number ?? 0) + 1;

        // 5 manches, scellé par décision PO (I-27).
        if ($nextNumber > (int) config('interflo.sealed.elimination_rounds')) {
            throw ValidationException::withMessages([
                'theme_id' => [__('interflo.pilot.rounds_exhausted')],
            ]);
        }

        $this->assertQuestionUsable($question, $nextNumber);

        return DB::transaction(function () use ($theme, $question, $nextNumber): GameRound {
            $question->update(['theme_id' => $theme->id]);

            return GameRound::query()->create([
                'theme_id' => $theme->id,
                'round_number' => $nextNumber,
                'question_id' => $question->id,
                'status' => GameRound::STATUS_PENDING,
            ]);
        });
    }

    /**
     * Ouvre la fenêtre de la manche (I-2 / EX-10). L'ouverture est un état
     * serveur : c'est window_opened_at qui autorise, pas un flag client.
     *
     * @throws ValidationException manche non en attente, thème terminé, ou
     *                             manche précédente non clôturée.
     */
    public function openRound(GameRound $round): GameRound
    {
        $theme = $round->theme;

        if ($theme->isFinished()) {
            throw ValidationException::withMessages([
                'round' => [__('interflo.pilot.theme_finished')],
            ]);
        }

        if ($round->status !== GameRound::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'round' => [__('interflo.pilot.round_not_pending')],
            ]);
        }

        // Les manches précédentes doivent être clôturées : une seule fenêtre
        // à la fois, et le verrouillage (EX-32) se déduit des manches closes.
        $previousOpen = $theme->rounds()
            ->where('round_number', '<', $round->round_number)
            ->where('status', '!=', GameRound::STATUS_CLOSED)
            ->exists();

        if ($previousOpen) {
            throw ValidationException::withMessages([
                'round' => [__('interflo.pilot.previous_round_not_closed')],
            ]);
        }

        $round->update([
            'window_opened_at' => now(),
            'status' => GameRound::STATUS_OPEN,
        ]);

        // Push temps réel (D-002 §4.1) : l'ouverture de la fenêtre et la
        // question sont poussées à la population de la session. Le polling
        // reste le transport dégradé (D-1). En test : BROADCAST_CONNECTION=null
        // (phpunit.xml) → no-op, aucun Reverb requis.
        $round->load('question');
        broadcast(new RoundOpened($theme, $round));

        return $round;
    }

    /**
     * Ferme la fenêtre de la manche (I-2 / EX-10). À la clôture, les joueurs
     * servis sans réponse dans leur fenêtre personnelle sont verrouillés par
     * déduction (EX-32 — voir EliminationSurvivorService).
     *
     * @throws ValidationException manche non ouverte.
     */
    public function closeRound(GameRound $round): GameRound
    {
        if (! $round->isOpen()) {
            throw ValidationException::withMessages([
                'round' => [__('interflo.pilot.round_not_open')],
            ]);
        }

        $round->update([
            'window_closed_at' => now(),
            'status' => GameRound::STATUS_CLOSED,
        ]);

        return $round;
    }

    /**
     * Fin de partie (I-28 / EX-35) — après la 5e manche clôturée.
     *
     * - 'all_survivors' : tous les survivants gagnent.
     * - 'draw' : tirage au sort SERVEUR de winners_count (1/3/5, options
     *   scellées I-28) parmi les survivants, via random_int.
     *
     * IDEMPOTENT : si les gagnants sont déjà persistés, ils sont renvoyés
     * tels quels — le tirage ne se rejoue jamais.
     *
     * ⚠️ Réglementaire (cadrage §10.4) : le tirage fait basculer le jeu de
     * l'adresse vers le hasard — cadre légal à instruire.
     *
     * @return Collection<int, GameThemeWinner>
     *
     * @throws ValidationException manches non toutes clôturées, ou
     *                             winners_count hors options scellées.
     */
    public function finishTheme(GameTheme $theme): Collection
    {
        // Idempotence : le tirage a déjà eu lieu, on renvoie la preuve.
        $existing = $theme->winners()->orderBy('rank')->get();
        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $roundsCount = (int) config('interflo.sealed.elimination_rounds');

        // Fin de partie après la 5e manche (I-28) : toutes les manches
        // doivent exister et être clôturées.
        $closedCount = $theme->rounds()->where('status', GameRound::STATUS_CLOSED)->count();
        if ($closedCount < $roundsCount) {
            throw ValidationException::withMessages([
                'theme' => [__('interflo.pilot.rounds_not_all_closed')],
            ]);
        }

        return DB::transaction(function () use ($theme): Collection {
            $survivors = $this->survivors->survivors($theme);
            $config = $this->tenantConfig($theme);

            if ($config?->isDrawEndgame()) {
                $winners = $this->drawWinners($survivors, (int) $config->winners_count);
            } else {
                // Tous les survivants gagnent : pas de rang.
                $winners = $survivors->map(fn ($survivor): array => [
                    'player_id' => $survivor->id,
                    'rank' => null,
                ])->all();
            }

            foreach ($winners as $winner) {
                GameThemeWinner::query()->create([
                    'theme_id' => $theme->id,
                    'player_id' => $winner['player_id'],
                    'rank' => $winner['rank'],
                ]);
            }

            $theme->update(['status' => GameTheme::STATUS_FINISHED]);

            return $theme->winners()->orderBy('rank')->get();
        });
    }

    /**
     * État du thème pour la console animateur : manche courante, fenêtre,
     * compteur de survivants (EX-33), participation.
     *
     * @return array<string, mixed>
     */
    public function pilotState(GameTheme $theme): array
    {
        // La console pilote la DERNIÈRE manche programmée (même en attente
        // d'ouverture) — c'est elle que l'animateur va ouvrir (I-2).
        $current = $theme->latestRound;

        return [
            'theme' => [
                'id' => $theme->id,
                'title' => $theme->title,
                'population' => $theme->population,
                'status' => $theme->status,
            ],
            'current_round' => $current === null ? null : [
                'id' => $current->id,
                'round_number' => $current->round_number,
                'status' => $current->status,
                'window_opened_at' => $current->window_opened_at?->toISOString(),
                'window_closed_at' => $current->window_closed_at?->toISOString(),
            ],
            // Compteur affiché au studio après chaque manche (EX-33).
            'survivors_count' => $this->survivors->survivorsCount($theme),
            'participants_count' => $this->survivors->participantsCount($theme),
            'server_time' => now()->toISOString(),
        ];
    }

    /**
     * Tirage au sort serveur (I-28) via random_int — cryptographiquement
     * sûr, exécuté une seule fois (idempotence par persistance).
     *
     * @param  Collection<int, Player>  $survivors
     * @return array<int, array{player_id: int, rank: int}>
     *
     * @throws ValidationException winners_count hors options scellées (I-28).
     */
    private function drawWinners(Collection $survivors, int $winnersCount): array
    {
        // Options scellées par décision PO (I-28) — doublon applicatif de la
        // contrainte CHECK en base.
        if (! in_array($winnersCount, config('interflo.sealed.winner_count_options'), true)) {
            throw ValidationException::withMessages([
                'winners_count' => [__('interflo.pilot.winners_count_invalid')],
            ]);
        }

        // Moins de survivants que de places : tous les survivants gagnent.
        $pool = $survivors->values();
        $count = min($winnersCount, $pool->count());

        // Tirage sans remise par random_int (I-28).
        $drawn = [];
        $available = $pool->all();
        for ($rank = 1; $rank <= $count; $rank++) {
            $key = random_int(0, count($available) - 1);
            $drawn[] = ['player_id' => $available[$key]->id, 'rank' => $rank];
            array_splice($available, $key, 1);
        }

        return $drawn;
    }

    /** Configuration de jeu du tenant propriétaire du thème (I-31, I-28). */
    private function tenantConfig(GameTheme $theme): ?TenantGameConfig
    {
        return $theme->gameSession->emission->tenant->gameConfig;
    }

    /**
     * EX-40 : pas de validation humaine, pas de diffusion — contrôle service.
     * Une question déjà programmée ou visant une autre manche est refusée.
     *
     * @throws ValidationException
     */
    private function assertQuestionUsable(Question $question, int $roundNumber, string $field = 'question_id'): void
    {
        if (! $question->isValidated()) {
            throw ValidationException::withMessages([
                $field => [__('interflo.pilot.question_not_validated')],
            ]);
        }

        if ($question->isUsed()) {
            throw ValidationException::withMessages([
                $field => [__('interflo.pilot.question_already_used')],
            ]);
        }

        // La question porte la manche qu'elle vise (1..5, scellé I-27) :
        // elle doit correspondre à la manche programmée.
        if ($question->round_number !== $roundNumber) {
            throw ValidationException::withMessages([
                $field => [__('interflo.pilot.question_round_mismatch')],
            ]);
        }
    }
}
