<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GameThemeWinnerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gagnant d'un thème d'élimination, persisté à la fin de partie (I-28).
 *
 * Le tirage au sort est exécuté côté serveur, une seule fois : l'existence
 * de lignes dans cette table rend la fin de partie idempotente.
 *
 * ⚠️ Réglementaire (cadrage §10.4) : le tirage fait basculer le jeu de
 * l'adresse vers le hasard — cadre légal à instruire.
 *
 * PROVISOIRE — docs/07 §5.
 */
class GameThemeWinner extends Model
{
    /** @use HasFactory<GameThemeWinnerFactory> */
    use HasFactory;

    protected $fillable = [
        'theme_id',
        'player_id',
        'rank',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
        ];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(GameTheme::class, 'theme_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
