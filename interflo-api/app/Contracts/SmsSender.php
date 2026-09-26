<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Contrat d'envoi de SMS.
 *
 * ⚠️ HYPOTHÈSE : aucun provider SMS n'est choisi à ce stade. Ce contrat est
 * la SEULE porte de sortie SMS du code : brancher un vrai provider reviendra
 * à écrire une nouvelle implémentation et à changer le binding dans
 * AppServiceProvider, sans toucher aux services.
 */
interface SmsSender
{
    /**
     * Envoie un SMS à un numéro au format E.164.
     */
    public function send(string $phone, string $message): void;
}
