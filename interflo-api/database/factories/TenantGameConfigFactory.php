<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\TenantGameConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantGameConfig>
 */
class TenantGameConfigFactory extends Factory
{
    protected $model = TenantGameConfig::class;

    /**
     * Configuration par défaut : toutes les valeurs métier à leur défaut
     * (fenêtre null = défaut paramétrable I-31, mode mesuré désactivé — à
     * arbitrer, EX-30 —, fin de partie « tous les survivants », I-28).
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'answer_window_seconds' => null,
            'measured_mode_enabled' => false,
            'endgame_rule' => TenantGameConfig::ENDGAME_ALL_SURVIVORS,
            'winners_count' => null,
            'persistent_ranking_enabled' => false,
        ];
    }

    /** Fin de partie par tirage au sort (I-28) avec un nombre scellé de gagnants. */
    public function drawEndgame(int $winnersCount = 3): static
    {
        return $this->state(fn (): array => [
            'endgame_rule' => TenantGameConfig::ENDGAME_DRAW,
            'winners_count' => $winnersCount,
        ]);
    }
}
