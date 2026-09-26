<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Phone\RequestPhoneCodeRequest;
use App\Http\Requests\Phone\VerifyPhoneCodeRequest;
use App\Http\Resources\PlayerResource;
use App\Services\PhoneVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Vérification du numéro de téléphone (I-8) : demande et vérification du code OTP.
 */
class PhoneVerificationController extends Controller
{
    public function __construct(
        private readonly PhoneVerificationService $service,
    ) {}

    public function requestCode(RequestPhoneCodeRequest $request): Response
    {
        // Réponse identique que le numéro soit connu ou non (non-énumération).
        $this->service->requestCode($request->validated('phone'));

        return response()->noContent();
    }

    public function verify(VerifyPhoneCodeRequest $request): JsonResponse
    {
        $player = $this->service->verify(
            $request->validated('phone'),
            $request->validated('code'),
        );

        // Échec générique : même message pour code faux, expiré, tentatives
        // dépassées ou numéro inconnu.
        if ($player === null) {
            throw ValidationException::withMessages([
                'code' => [__('interflo.phone_code_invalid')],
            ]);
        }

        return response()->json([
            'token' => $player->createToken('player')->plainTextToken,
            'player' => new PlayerResource($player),
        ]);
    }
}
