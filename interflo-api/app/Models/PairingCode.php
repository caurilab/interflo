<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PairingCodeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Code d'appairage rotatif (I-18) : pointeur public vers chaîne + émission
 * (I-15 / INV-6). Un code expiré ne résout plus (EX-04).
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class PairingCode extends Model
{
    /** @use HasFactory<PairingCodeFactory> */
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'code',
        'valid_from',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
        ];
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    /** Les codes actuellement valides (fenêtre [valid_from, valid_until]). */
    public function scopeCurrentlyValid(Builder $query): Builder
    {
        return $query->where('valid_from', '<=', now())
            ->where('valid_until', '>', now());
    }
}
