<?php

declare(strict_types=1);

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use Illuminate\Support\Facades\Log;

/**
 * Implémentation par défaut du contrat SmsSender : écrit le message dans le
 * log au lieu de l'envoyer. ⚠️ Aucun provider SMS n'est choisi — tant qu'un
 * vrai provider n'est pas branché, les OTP vivent dans les logs.
 */
class LogSmsSender implements SmsSender
{
    public function send(string $phone, string $message): void
    {
        Log::info('SMS (log) — envoi simulé', [
            'phone' => $phone,
            'message' => $message,
        ]);
    }
}
