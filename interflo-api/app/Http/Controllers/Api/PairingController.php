<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pairing\ResolvePairingCodeRequest;
use App\Http\Resources\PairingResolutionResource;
use App\Services\PairingCodeService;

/**
 * Résolution publique d'un code d'appairage (I-14, I-15).
 *
 * Le code DÉSIGNE une chaîne et une émission — il n'autorise rien (R-9).
 */
class PairingController extends Controller
{
    public function __construct(
        private readonly PairingCodeService $service,
    ) {}

    public function resolve(ResolvePairingCodeRequest $request): PairingResolutionResource
    {
        $session = $this->service->resolve($request->validated('code'));

        // 404 générique : code inconnu ou expiré (EX-04), sans distinction.
        abort_if($session === null, 404, __('interflo.pairing_code_not_found'));

        return new PairingResolutionResource($session);
    }
}
