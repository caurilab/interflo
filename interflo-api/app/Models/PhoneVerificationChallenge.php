<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PhoneVerificationChallengeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Challenge de vérification d'un numéro de téléphone (I-8).
 *
 * Le code OTP n'est jamais stocké en clair : uniquement son hash.
 * Le joueur peut ne pas exister encore — pas de clé étrangère.
 *
 * PROVISOIRE — en attente de la décision sur le modèle de données (docs/07 §5).
 */
class PhoneVerificationChallenge extends Model
{
    /** @use HasFactory<PhoneVerificationChallengeFactory> */
    use HasFactory;

    protected $fillable = [
        'phone',
        'code_hash',
        'expires_at',
        'attempts',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /** Le challenge peut-il encore être tenté ? */
    public function isPending(): bool
    {
        return $this->consumed_at === null && $this->expires_at->isFuture();
    }
}
