<?php

declare(strict_types=1);

namespace App\Http\Requests\Phone;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la vérification d'un code OTP (I-8). Route publique.
 */
class VerifyPhoneCodeRequest extends FormRequest
{
    /**
     * Route publique : la vérification du code EST l'acte d'authentification
     * du joueur. Tout visiteur peut tenter un code (borné par otp_max_attempts
     * et le rate limiting).
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{1,14}$/'],
            // La longueur du code suit la configuration (point de départ non validé).
            'code' => ['required', 'string', 'digits:'.(int) config('interflo.otp_length', 6)],
        ];
    }
}
