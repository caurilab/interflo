<?php

declare(strict_types=1);

namespace App\Http\Requests\Pairing;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la résolution d'un code d'appairage (I-14, I-15).
 *
 * Route publique : le code DÉSIGNE, il n'autorise rien (R-9 / INV-6).
 */
class ResolvePairingCodeRequest extends FormRequest
{
    /**
     * Le code d'appairage est un pointeur public diffusé à l'antenne :
     * tout visiteur peut le résoudre.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32'],
        ];
    }

    /**
     * Normalise le code : l'alphabet est en majuscules, la saisie manuelle
     * ne doit pas être sensible à la casse.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }
}
