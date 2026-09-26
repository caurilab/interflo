<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\GameSession;
use App\Services\PairingCodeService;
use Illuminate\Console\Command;

/**
 * Fait tourner les codes d'appairage des sessions en direct (I-18).
 *
 * ⚠️ LIMITATION documentée : le scheduler Laravel a une granularité d'une
 * minute, alors que la période de rotation envisagée est de 15 à 30 secondes
 * (point de départ non validé : 20 s). Cette commande est une mécanique de
 * secours ; la rotation en cadence réelle dépend de l'architecture temps
 * réel, non conçue (docs/04-architecture.md §5). La validité d'un code reste
 * bornée par ses timestamps — un code expiré ne résout plus (EX-04), même si
 * aucun nouveau code n'a encore été généré.
 */
class RotatePairingCodes extends Command
{
    protected $signature = 'interflo:rotate-pairing-codes';

    protected $description = 'Fait tourner les codes d\'appairage des sessions en direct (I-18)';

    public function handle(PairingCodeService $service): int
    {
        $count = 0;

        GameSession::query()
            ->where('status', GameSession::STATUS_LIVE)
            ->each(function (GameSession $session) use ($service, &$count): void {
                $service->rotate($session);
                $count++;
            });

        $this->info("{$count} code(s) d'appairage tourné(s).");

        return self::SUCCESS;
    }
}
