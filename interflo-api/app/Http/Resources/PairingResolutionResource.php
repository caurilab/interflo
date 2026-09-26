<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\GameSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pointeur public renvoyé par la résolution d'un code d'appairage.
 *
 * INV-6 / R-9 : le code DÉSIGNE (chaîne + émission + session), il n'autorise
 * rien. R-11 : charge utile minimale — ni slug, ni références BOS, rien
 * d'autre que ce que le mobile affiche et consomme.
 *
 * @mixin GameSession
 */
class PairingResolutionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tenant' => [
                'name' => $this->emission->tenant->name,
            ],
            'emission' => [
                'title' => $this->emission->title,
            ],
            'session' => [
                'id' => $this->id,
                'status' => $this->status,
            ],
        ];
    }
}
