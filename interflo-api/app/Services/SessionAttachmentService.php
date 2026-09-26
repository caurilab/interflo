<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GameSession;
use App\Models\Player;
use App\Models\PlayerSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Attachement / détachement d'un joueur à une session de jeu.
 *
 * I-16 : une session terminée refuse tout attachement — l'accès est mort.
 * I-17 : une seule session active par joueur — garantie en base par l'index
 * unique partiel, et en service par le détachement des autres sessions.
 */
class SessionAttachmentService
{
    /**
     * Attache le joueur à la session et détache toute autre session active.
     *
     * @throws ValidationException si la session est terminée (I-16).
     */
    public function attach(Player $player, GameSession $session): PlayerSession
    {
        // Émission terminée, accès mort (I-16) : le serveur refuse (INV-8).
        if ($session->hasEnded()) {
            throw ValidationException::withMessages([
                'session_id' => [__('interflo.session_ended')],
            ]);
        }

        return DB::transaction(function () use ($player, $session): PlayerSession {
            // I-17 : détache toute autre session active avant d'attacher.
            PlayerSession::query()
                ->where('player_id', $player->id)
                ->active()
                ->where('game_session_id', '!=', $session->id)
                ->update(['detached_at' => now()]);

            // Ré-attachement idempotent à la même session : on renvoie
            // l'attachement actif existant plutôt qu'un doublon.
            /** @var PlayerSession|null $existing */
            $existing = PlayerSession::query()
                ->where('player_id', $player->id)
                ->where('game_session_id', $session->id)
                ->active()
                ->first();

            return $existing ?? PlayerSession::query()->create([
                'player_id' => $player->id,
                'game_session_id' => $session->id,
                'attached_at' => now(),
            ]);
        });
    }

    /**
     * Détache la session active du joueur, s'il en a une. Idempotent.
     */
    public function detach(Player $player): void
    {
        PlayerSession::query()
            ->where('player_id', $player->id)
            ->active()
            ->update(['detached_at' => now()]);
    }
}
