<?php

declare(strict_types=1);

namespace App\Http\Resources\Pilot;

use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Question de la banque, côté console animateur (liste de sélection).
 *
 * ⚠️ INV-2 / CA-07 : `correct_index` ne sort JAMAIS du serveur — le pilotage
 * n'a pas besoin de la bonne réponse (elle n'apparaît que dans Filament).
 *
 * @mixin Question
 */
class QuestionListItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'round_number' => $this->round_number,
            'body' => $this->body,
            'source' => $this->source,
        ];
    }
}
