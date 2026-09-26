<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Représentation API d'un joueur — uniquement SES propres données.
 *
 * @mixin Player
 */
class PlayerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Charge utile économe (R-11) : le strict nécessaire.
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'phone_verified_at' => $this->phone_verified_at?->toIso8601String(),
        ];
    }
}
