<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\RoundPlayerStateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * État d'un joueur sur une manche : service, réponse, verdict serveur.
 *
 * ⚠️ PERSISTANCE PROVISOIRE (docs/07 §5) : la réponse est une ligne
 * relationnelle par geste. Correct fonctionnellement, ne prétend PAS tenir
 * le pic d'écriture du direct — en attente de l'ADR temps réel.
 *
 * Motifs de rejet serveur (rejected_reason) : une réponse rejetée ne
 * consomme pas le droit de répondre — answered_at reste null.
 *
 * PROVISOIRE — docs/07 §5.
 */
class RoundPlayerState extends Model
{
    /** @use HasFactory<RoundPlayerStateFactory> */
    use HasFactory;

    // Motifs de rejet serveur (audit provisoire).
    public const REJECTED_WINDOW_CLOSED = 'window_closed';

    public const REJECTED_IMPOSSIBLE_TIMESTAMP = 'impossible_timestamp';

    public const REJECTED_TOO_FAST = 'too_fast';

    public const REJECTED_WINDOW_EXCEEDED = 'window_exceeded';

    protected $fillable = [
        'round_id',
        'player_id',
        'served_at',
        'answered_at',
        'answer_index',
        'is_correct',
        'rejected_reason',
    ];

    protected function casts(): array
    {
        return [
            'served_at' => 'datetime',
            'answered_at' => 'datetime',
            'answer_index' => 'integer',
            'is_correct' => 'boolean',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(GameRound::class, 'round_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /** Le joueur a-t-il répondu (réponse acceptée) ? */
    public function hasAnswered(): bool
    {
        return $this->answered_at !== null;
    }
}
