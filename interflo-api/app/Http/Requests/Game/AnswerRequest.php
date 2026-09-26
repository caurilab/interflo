<?php

declare(strict_types=1);

namespace App\Http\Requests\Game;

use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Soumission d'une réponse (format élimination).
 *
 * answer_index : 0..3 (scellé I-4). client_timestamp : horodatage du geste
 * sur l'appareil, en millisecondes epoch (I-6) — borné et validé côté
 * service, jamais cru (INV-3 / R-6).
 */
class AnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Réservé aux joueurs authentifiés au numéro vérifié (I-8) — le
        // middleware phone.verified a déjà refusé les autres.
        return $this->user() instanceof Player;
    }

    public function rules(): array
    {
        return [
            'answer_index' => ['required', 'integer', 'min:0', 'max:3'],
            'client_timestamp' => ['required', 'integer', 'min:1'],
        ];
    }
}
