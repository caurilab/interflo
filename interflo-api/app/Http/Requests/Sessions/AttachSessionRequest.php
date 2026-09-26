<?php

declare(strict_types=1);

namespace App\Http\Requests\Sessions;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de l'attachement d'un joueur à une session (I-17).
 */
class AttachSessionRequest extends FormRequest
{
    /**
     * L'authentification Sanctum et la vérification du téléphone sont
     * exigées par les middlewares de la route ; ici, seul un joueur
     * authentifié atteint ce point.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:game_sessions,id'],
        ];
    }
}
