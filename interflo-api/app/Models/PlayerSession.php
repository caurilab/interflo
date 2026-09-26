<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PlayerSessionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attachement d'un joueur à une session de jeu.
 *
 * Une seule session active par joueur (I-17) — garantie en base par un index
 * unique partiel sur (player_id) WHERE detached_at IS NULL.
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class PlayerSession extends Model
{
    /** @use HasFactory<PlayerSessionFactory> */
    use HasFactory;

    // Nom de table singulier, exigé par la mission (pivot enrichi).
    protected $table = 'player_session';

    protected $fillable = [
        'player_id',
        'game_session_id',
        'attached_at',
        'detached_at',
    ];

    protected function casts(): array
    {
        return [
            'attached_at' => 'datetime',
            'detached_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    /** Attachements encore actifs. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('detached_at');
    }
}
