<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GameSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Session de jeu : vit et meurt avec l'émission (I-16).
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class GameSession extends Model
{
    /** @use HasFactory<GameSessionFactory> */
    use HasFactory;

    // Statuts possibles d'une session de jeu.
    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_LIVE = 'live';

    public const STATUS_ENDED = 'ended';

    protected $fillable = [
        'emission_id',
        'status',
        // ⚠️ Auth animateur PROVISOIRE : token opaque par session, porté par
        // l'en-tête X-Pilot-Token. Non spécifié par les documents — à
        // remplacer dès arbitrage (voir migration 2026_09_27_000002).
        'pilot_token',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function emission(): BelongsTo
    {
        return $this->belongsTo(Emission::class);
    }

    public function pairingCodes(): HasMany
    {
        return $this->hasMany(PairingCode::class);
    }

    public function playerSessions(): HasMany
    {
        return $this->hasMany(PlayerSession::class);
    }

    /** Émission terminée, accès mort (I-16). */
    public function hasEnded(): bool
    {
        return $this->status === self::STATUS_ENDED;
    }

    /** Code d'appairage actuellement valide, ou null si aucun (I-18). */
    public function currentPairingCode(): ?PairingCode
    {
        return $this->pairingCodes()->currentlyValid()->latest('id')->first();
    }

    /** Thèmes (parties d'élimination, I-27) joués dans cette session. */
    public function gameThemes(): HasMany
    {
        return $this->hasMany(GameTheme::class);
    }

    protected static function booted(): void
    {
        // ⚠️ Auth animateur PROVISOIRE : génération d'un token opaque à la
        // création de la session (voir migration 2026_09_27_000002).
        static::creating(function (GameSession $session): void {
            $session->pilot_token ??= Str::random((int) config('interflo.pilot_token_length'));
        });
    }
}
