<?php

declare(strict_types=1);

namespace App\Http\Requests\Pilot;

use App\Models\GameSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Programmation de la manche suivante d'un thème (réservée aux survivants,
 * EX-32) depuis une question validée (EX-40 — contrôle côté service).
 *
 * ⚠️ Auth animateur PROVISOIRE (middleware pilot.token) — voir
 * EnsurePilotToken.
 */
class CreatePilotRoundRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La session pilotée a été résolue par le middleware pilot.token.
        return $this->attributes->get('pilot_session') instanceof GameSession;
    }

    public function rules(): array
    {
        return [
            'theme_id' => ['required', 'integer', Rule::exists('game_themes', 'id')],
            'question_id' => ['required', 'integer', Rule::exists('questions', 'id')],
        ];
    }
}
