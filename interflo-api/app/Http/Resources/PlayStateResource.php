<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * État de jeu courant pour le joueur (polling PROVISOIRE — en attente de
 * l'ADR temps réel). Enveloppe construite par PlayerGameStateService.
 *
 * ⚠️ CA-07 / INV-2 : la charge utile ne contient JAMAIS correct_index.
 */
class PlayStateResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
