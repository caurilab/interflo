<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GameThemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Thème de jeu : une partie d'élimination (5 manches, I-27) dans une session.
 *
 * Un thème concerne UNE seule population (I-1 / EX-23) : studio ou domicile.
 * Les deux populations ne concourent jamais l'une contre l'autre.
 *
 * PROVISOIRE — docs/07 §5.
 */
class GameTheme extends Model
{
    /** @use HasFactory<GameThemeFactory> */
    use HasFactory;

    // Statuts possibles d'un thème.
    public const STATUS_ACTIVE = 'active';

    public const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'game_session_id',
        'title',
        'population',
        'status',
    ];

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'theme_id');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(GameRound::class, 'theme_id');
    }

    public function winners(): HasMany
    {
        return $this->hasMany(GameThemeWinner::class, 'theme_id');
    }

    /**
     * Manche courante de JEU : la plus avancée ACTIVÉE (ouverte ou clôturée).
     * Une manche programmée mais jamais ouverte (pending) n'est pas la
     * manche courante — les manches peuvent être pré-programmées.
     */
    public function currentRound(): HasOne
    {
        return $this->hasOne(GameRound::class, 'theme_id')
            ->where('status', '!=', GameRound::STATUS_PENDING)
            ->latest('round_number');
    }

    /** Dernière manche programmée, quel que soit son statut. */
    public function latestRound(): HasOne
    {
        return $this->hasOne(GameRound::class, 'theme_id')->latest('round_number');
    }

    /** Fin de partie atteinte (I-28) ? */
    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    /** La partie concerne-t-elle cette population ? (I-1 : jamais mixte.) */
    public function concernsPopulation(string $population): bool
    {
        return $this->population === $population;
    }
}
