<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GameRound;
use App\Models\GameTheme;
use App\Models\Player;
use App\Models\RoundPlayerState;

/**
 * État de jeu courant pour un joueur — frontière joueur, transport PROVISOIRE
 * par polling HTTP sobre (en attente de l'ADR temps réel, 04-architecture §5).
 * La mécanique, elle, est définitive.
 *
 * États exposés : idle (rien à jouer), waiting (entre deux manches),
 * question (fenêtre ouverte, question servie), answered (réponse acceptée),
 * locked (verrouillé jusqu'à la fin du thème, EX-32), finished (fin de
 * partie, I-28 — avec le bit 'winner' pour CE joueur uniquement).
 *
 * ⚠️ INV-2 / R-4 / CA-07 : la question servie ne porte JAMAIS correct_index —
 * body + 4 propositions (scellé I-4), rien d'autre.
 *
 * EX-16 (provisoire) : chaque réponse inclut server_time pour que le client
 * calcule son offset d'horloge.
 */
class PlayerGameStateService
{
    public function __construct(
        private readonly EliminationSurvivorService $survivors,
    ) {}

    /**
     * Construit l'état courant pour le joueur. Effet de bord assumé et
     * documenté : si une fenêtre est ouverte et que le joueur est éligible,
     * la question lui est SERVIE (served_at posé une fois, EX-20) — le
     * premier poll démarre sa fenêtre personnelle.
     *
     * @return array<string, mixed>
     */
    public function stateFor(Player $player): array
    {
        $serverTime = now()->toISOString();

        $attachment = $player->activePlayerSession;
        if ($attachment === null || $attachment->gameSession->hasEnded()) {
            return ['state' => 'idle', 'server_time' => $serverTime];
        }

        $session = $attachment->gameSession;

        // Thème courant pour la population du joueur (I-1) : le plus récent,
        // qu'il soit actif ou terminé.
        /** @var GameTheme|null $theme */
        $theme = $session->gameThemes()
            ->where('population', $player->population)
            ->latest('id')
            ->first();

        if ($theme === null) {
            return ['state' => 'idle', 'server_time' => $serverTime];
        }

        // Fin de partie (I-28) : le joueur sait s'il gagne — un bit, pas la
        // liste des gagnants.
        if ($theme->isFinished()) {
            return [
                'state' => 'finished',
                'server_time' => $serverTime,
                'theme' => ['id' => $theme->id, 'title' => $theme->title],
                'winner' => $theme->winners()->where('player_id', $player->id)->exists(),
            ];
        }

        // Verrouillé jusqu'à la fin du thème (EX-32) — déduit.
        if ($this->survivors->isLocked($player, $theme)) {
            return [
                'state' => 'locked',
                'server_time' => $serverTime,
                'theme' => ['id' => $theme->id, 'title' => $theme->title],
            ];
        }

        /** @var GameRound|null $round */
        $round = $theme->currentRound;

        // Entre deux manches : la fenêtre n'est pas (ou plus) ouverte (I-2).
        if ($round === null || ! $round->isOpen()) {
            return [
                'state' => 'waiting',
                'server_time' => $serverTime,
                'theme' => ['id' => $theme->id, 'title' => $theme->title],
                'round_number' => $round?->round_number,
            ];
        }

        // Fenêtre ouverte : sert la question à ce joueur (une seule fois —
        // firstOrCreate + index unique (round_id, player_id)) ou retrouve son
        // état existant. Le served_at initial n'est JAMAIS réinitialisé.
        /** @var RoundPlayerState $state */
        $state = RoundPlayerState::query()->firstOrCreate(
            ['round_id' => $round->id, 'player_id' => $player->id],
            ['served_at' => now()],
        );

        // Le joueur a déjà répondu : il attend la manche suivante.
        if ($state->hasAnswered()) {
            return [
                'state' => 'answered',
                'server_time' => $serverTime,
                'theme' => ['id' => $theme->id, 'title' => $theme->title],
                'round_number' => $round->round_number,
                // Son propre verdict, un bit (R-5) — jamais la bonne réponse.
                'correct' => (bool) $state->is_correct,
            ];
        }

        $windowSeconds = $theme->gameSession->emission->tenant->gameConfig?->effectiveAnswerWindowSeconds()
            ?? (int) config('interflo.answer_window_seconds');

        return [
            'state' => 'question',
            'server_time' => $serverTime,
            'theme' => ['id' => $theme->id, 'title' => $theme->title],
            'round' => [
                'id' => $round->id,
                'round_number' => $round->round_number,
                // Base de la fenêtre PERSONNELLE du joueur (EX-20).
                'served_at' => $state->served_at->toISOString(),
                'window_seconds' => $windowSeconds,
                // ⚠️ INV-2 : body + 4 propositions SEULEMENT (scellé I-4) —
                // correct_index ne sort jamais (CA-07).
                'question' => [
                    'body' => $round->question->body,
                    'propositions' => array_values($round->question->propositions),
                ],
            ],
        ];
    }
}
