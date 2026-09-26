<?php

declare(strict_types=1);

namespace App\Http\Resources\Pilot;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * État d'un thème pour la console animateur : manche courante, fenêtre,
 * compteur de survivants (EX-33), participation. Enveloppe construite par
 * EliminationThemeService::pilotState().
 */
class ThemeStateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
