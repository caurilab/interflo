<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GameRound;
use App\Models\GameTheme;
use App\Models\Player;
use App\Models\RoundPlayerState;
use Illuminate\Support\Collection;

/**
 * Survivants et verrouillage du format élimination (I-27, EX-32, EX-33).
 *
 * ⚠️ CHOIX DOCUMENTÉ — le verrouillage est DÉDUIT, pas matérialisé :
 * un joueur est verrouillé jusqu'à la fin du thème dès qu'il existe une
 * manche CLÔTURÉE du thème pour laquelle il ne porte pas de verdict correct
 * (is_correct = true) — que ce soit par erreur (EX-32) ou par absence de
 * réponse dans sa fenêtre personnelle (EX-20). Aucune colonne de verrou :
 * l'état se recalcule à chaque contrôle, il ne peut pas diverger des
 * réponses effectivement enregistrées.
 *
 * Conséquence assumée : un joueur qui n'a jamais été servi sur une manche
 * clôturée est verrouillé comme celui qui s'est trompé — la manche suivante
 * est « réservée aux survivants » (mission §1), personne ne rejoint une
 * partie en cours de route.
 *
 * PROVISOIRE — docs/07 §5.
 */
class EliminationSurvivorService
{
    /**
     * Le joueur est-il verrouillé sur ce thème ? (EX-32)
     *
     * Verrouillé = au moins une manche clôturée du thème sans verdict correct
     * pour lui. Le verrouillage tient jusqu'à la FIN DU THÈME : une bonne
     * réponse ultérieure ne déverrouille jamais (et de toute façon un joueur
     * verrouillé ne peut plus répondre — AnswerService).
     */
    public function isLocked(Player $player, GameTheme $theme): bool
    {
        // Manches clôturées du thème.
        $closedRoundIds = $this->closedRoundIds($theme);

        if ($closedRoundIds->isEmpty()) {
            return false;
        }

        // Manches clôturées pour lesquelles le joueur a un verdict correct.
        $survivedCount = RoundPlayerState::query()
            ->whereIn('round_id', $closedRoundIds)
            ->where('player_id', $player->id)
            ->where('is_correct', true)
            ->distinct()
            ->count('round_id');

        return $survivedCount < $closedRoundIds->count();
    }

    /**
     * Le joueur peut-il répondre à cette manche ? Survivant du thème, de la
     * bonne population (I-1) et manche suivante réservée aux survivants.
     */
    public function isEligible(Player $player, GameRound $round): bool
    {
        return $player->belongsToPopulation($round->theme)
            && ! $this->isLocked($player, $round->theme);
    }

    /**
     * Survivants actuels du thème : joueurs ayant un verdict correct sur
     * TOUTES les manches clôturées. Base du compteur studio (EX-33) et de la
     * fin de partie (I-28). Aucune manche clôturée → tous les participants.
     *
     * @return Collection<int, Player>
     */
    public function survivors(GameTheme $theme): Collection
    {
        $closedRoundIds = $this->closedRoundIds($theme);

        $query = RoundPlayerState::query()
            ->whereIn('round_id', $this->roundIds($theme))
            ->where('is_correct', true);

        if ($closedRoundIds->isNotEmpty()) {
            // Correct sur CHAQUE manche clôturée.
            $query->whereIn('round_id', $closedRoundIds)
                ->groupBy('player_id')
                ->havingRaw('COUNT(DISTINCT round_id) = ?', [$closedRoundIds->count()]);
        } else {
            $query->groupBy('player_id');
        }

        return Player::query()->whereIn('id', $query->pluck('player_id'))->get();
    }

    /** Compteur de survivants affiché au studio après chaque manche (EX-33). */
    public function survivorsCount(GameTheme $theme): int
    {
        return $this->survivors($theme)->count();
    }

    /** Participation : joueurs ayant été servis au moins une fois sur le thème. */
    public function participantsCount(GameTheme $theme): int
    {
        return RoundPlayerState::query()
            ->whereIn('round_id', $this->roundIds($theme))
            ->distinct()
            ->count('player_id');
    }

    /** @return Collection<int, int> identifiants des manches clôturées du thème. */
    private function closedRoundIds(GameTheme $theme): Collection
    {
        return $theme->rounds()
            ->where('status', GameRound::STATUS_CLOSED)
            ->pluck('id');
    }

    /** @return Collection<int, int> identifiants de toutes les manches du thème. */
    private function roundIds(GameTheme $theme): Collection
    {
        return $theme->rounds()->pluck('id');
    }
}
