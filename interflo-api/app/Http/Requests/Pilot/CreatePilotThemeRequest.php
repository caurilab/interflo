<?php

declare(strict_types=1);

namespace App\Http\Requests\Pilot;

use App\Models\GameSession;
use App\Models\Player;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Création d'un thème d'élimination (une partie, I-27) + sa première manche.
 *
 * ⚠️ Auth animateur PROVISOIRE (middleware pilot.token) — voir
 * EnsurePilotToken.
 */
class CreatePilotThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La session pilotée a été résolue par le middleware pilot.token.
        return $this->attributes->get('pilot_session') instanceof GameSession;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            // I-1 : une partie concerne UNE population — jamais les deux.
            'population' => ['required', Rule::in([Player::POPULATION_STUDIO, Player::POPULATION_HOME])],
            // Première manche depuis une question de la banque — la validation
            // humaine (EX-40) est contrôlée côté service.
            'first_question_id' => ['required', 'integer', Rule::exists('questions', 'id')],
        ];
    }
}
