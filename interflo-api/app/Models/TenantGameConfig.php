<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TenantGameConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuration de jeu d'un tenant (docs/07 §2.1) : fenêtre de réponse (I-31),
 * mode mesuré (I-25), règle de fin de partie (I-28), classement persistant (I-40).
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class TenantGameConfig extends Model
{
    /** @use HasFactory<TenantGameConfigFactory> */
    use HasFactory;

    // Règles de fin de partie possibles (I-28 / EX-35).
    public const ENDGAME_ALL_SURVIVORS = 'all_survivors';

    // ⚠️ Tirage au sort : bascule le jeu de l'adresse vers le hasard
    // (conséquence réglementaire, §10.4 du cadrage).
    public const ENDGAME_DRAW = 'draw';

    protected $fillable = [
        'tenant_id',
        'answer_window_seconds',
        'measured_mode_enabled',
        'endgame_rule',
        'winners_count',
        'persistent_ranking_enabled',
    ];

    protected function casts(): array
    {
        return [
            'answer_window_seconds' => 'integer',
            'measured_mode_enabled' => 'boolean',
            'winners_count' => 'integer',
            'persistent_ranking_enabled' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Durée de fenêtre effective : la valeur du tenant si renseignée, sinon le
     * défaut paramétrable config('interflo.answer_window_seconds') (I-31).
     */
    public function effectiveAnswerWindowSeconds(): int
    {
        return $this->answer_window_seconds ?? (int) config('interflo.answer_window_seconds');
    }

    /** Fin de partie par tirage au sort (I-28) — cadre légal à instruire. */
    public function isDrawEndgame(): bool
    {
        return $this->endgame_rule === self::ENDGAME_DRAW;
    }
}
