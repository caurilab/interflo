<?php

declare(strict_types=1);

namespace App\Http\Requests\Phone;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la demande de code OTP (I-8). Route publique.
 */
class RequestPhoneCodeRequest extends FormRequest
{
    /**
     * Route publique d'identification par téléphone : tout visiteur peut
     * demander un code. L'anti-abus est porté par le rate limiting, pas par
     * une autorisation.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Numéro au format E.164 : + suivi de 1 à 15 chiffres, sans zéro initial.
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{1,14}$/'],
        ];
    }
}
