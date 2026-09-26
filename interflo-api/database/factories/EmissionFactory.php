<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Emission;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Emission>
 */
class EmissionFactory extends Factory
{
    protected $model = Emission::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'title' => $this->faker->sentence(3),
            'external_reference' => null,
        ];
    }
}
