<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GameRoundFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Manche d'un thème d'élimination (5 manches, scellé I-27).
 *
 * L'autorisation de répondre est un ÉTAT SERVEUR (I-2 / R-3 / INV-8) :
 * l'animateur ouvre et ferme la fenêtre (window_opened_at / window_closed_at),
 * et hors fenêtre le serveur refuse. La fenêtre individuelle de chaque joueur
 * est PERSONNELLE (EX-20) : elle court depuis son served_at
 * (round_player_states), dans l'enveloppe collective de la manche.
 *
 * PROVISOIRE — docs/07 §5.
 */
class GameRound extends Model
{
    /** @use HasFactory<GameRoundFactory> */
    use HasFactory;

    // Statuts possibles d'une manche.
    public const STATUS_PENDING = 'pending';

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'theme_id',
        'round_number',
        'question_id',
        'window_opened_at',
        'window_closed_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'round_number' => 'integer',
            'window_opened_at' => 'datetime',
            'window_closed_at' => 'datetime',
        ];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(GameTheme::class, 'theme_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function playerStates(): HasMany
    {
        return $this->hasMany(RoundPlayerState::class, 'round_id');
    }

    /**
     * La fenêtre collective de la manche est-elle ouverte ? (I-2) — état
     * serveur : ouverte par l'animateur, pas encore fermée.
     */
    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN
            && $this->window_opened_at !== null
            && $this->window_closed_at === null;
    }
}
