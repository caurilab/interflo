<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Redis;

/**
 * État chaud des réponses pendant une fenêtre (D-002 §4.3).
 *
 * Pendant la fenêtre, la réponse d'un joueur est écrite dans Redis (hash par
 * manche) — unicité atomique via HSETNX. PostgreSQL n'est PAS dans le chemin
 * critique du pic d'écriture : un job en file (App\Jobs\PersistAnswer) flush
 * la réponse vers PostgreSQL, qui reste le système d'enregistrement.
 *
 * ⚠️ PROVISOIRE — D-002 n'est pas validée par le PO ; cette couche est le
 * socle de la persistance au pic, à exercer par test de charge (D-002 §7).
 */
class GameStateRedis
{
    /** Préfixe des clés de jeu (évite toute collision avec cache/session). */
    private const PREFIX = 'interflo:game:';

    /** Hash des réponses d'une manche : field = player_id, value = JSON. */
    private function answersKey(int $roundId): string
    {
        return self::PREFIX."round:{$roundId}:answers";
    }

    /**
     * Enregistre la réponse du joueur. Retourne true si c'était la PREMIÈRE
     * réponse de ce joueur sur cette manche (unicité atomique — HSETNX),
     * false s'il avait déjà répondu.
     */
    public function recordAnswer(int $roundId, int $playerId, int $answerIndex, bool $correct): bool
    {
        $value = json_encode([
            'answer_index' => $answerIndex,
            'correct' => $correct,
            'answered_at' => now()->toISOString(),
        ], JSON_THROW_ON_ERROR);

        // HSETNX : pose le champ seulement s'il n'existe pas encore.
        return (bool) Redis::hsetnx($this->answersKey($roundId), (string) $playerId, $value);
    }

    /** Toutes les réponses d'une manche (pour le flush), field => JSON. */
    public function answers(int $roundId): array
    {
        return Redis::hgetall($this->answersKey($roundId)) ?: [];
    }

    /** Purge les réponses d'une manche (après leur flush vers PostgreSQL). */
    public function clear(int $roundId): void
    {
        Redis::del($this->answersKey($roundId));
    }
}
