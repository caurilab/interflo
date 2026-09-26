<?php

declare(strict_types=1);

use App\Http\Controllers\Api\PairingController;
use App\Http\Controllers\Api\PhoneVerificationController;
use App\Http\Controllers\Api\Pilot\PilotQuestionController;
use App\Http\Controllers\Api\Pilot\PilotRoundController;
use App\Http\Controllers\Api\Pilot\PilotThemeController;
use App\Http\Controllers\Api\PlayStateController;
use App\Http\Controllers\Api\RoundAnswerController;
use App\Http\Controllers\Api\SessionAttachmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — frontière Joueur (docs/09-contrat-api.md §2)
|--------------------------------------------------------------------------
|
| Routes joueur : appairage (pointeur public, R-9) et vérification du
| numéro de téléphone (I-8). Aucune route de jeu ici : le contrat n'est
| pas écrit (docs/09 §1).
|
*/

Route::prefix('v1')->group(function (): void {

    // Vérification du numéro de téléphone (I-8) — routes publiques,
    // réponses identiques quel que soit le numéro (non-énumération),
    // rate limiting anti-abus.
    Route::post('players/phone/request-code', [PhoneVerificationController::class, 'requestCode'])
        ->middleware('throttle:otp-request');
    Route::post('players/phone/verify', [PhoneVerificationController::class, 'verify'])
        ->middleware('throttle:otp-verify');

    // Résolution d'un code d'appairage (I-14, I-15) — route publique :
    // le code désigne, il n'autorise rien (INV-6 / R-9).
    Route::post('pairing/resolve', [PairingController::class, 'resolve']);

    // Attachement à une session (I-16, I-17) — joueur authentifié au
    // numéro vérifié uniquement.
    Route::middleware(['auth:sanctum', 'phone.verified'])->group(function (): void {
        Route::post('sessions/attach', [SessionAttachmentController::class, 'store']);
        Route::delete('sessions/attach', [SessionAttachmentController::class, 'destroy']);

        // Jeu au format élimination (I-26, I-27) — frontière joueur.
        // Transport PROVISOIRE : polling HTTP sobre, en attente de l'ADR
        // temps réel (04-architecture §5). La mécanique est définitive.
        Route::get('play/state', [PlayStateController::class, 'show']);
        Route::post('rounds/{round}/answer', [RoundAnswerController::class, 'store']);
    });

    // Pilotage animateur — console tablette (§15.3 cadrage).
    // ⚠️ Auth PROVISOIRE : token opaque par session, en-tête X-Pilot-Token
    // (middleware pilot.token) — l'auth animateur n'est spécifiée nulle part,
    // voir EnsurePilotToken. À remplacer dès arbitrage.
    Route::prefix('pilot')->middleware('pilot.token')->group(function (): void {
        Route::post('themes', [PilotThemeController::class, 'store']);
        Route::get('themes/{theme}/state', [PilotThemeController::class, 'state']);
        Route::post('themes/{theme}/finish', [PilotThemeController::class, 'finish']);
        Route::post('rounds', [PilotRoundController::class, 'store']);
        Route::post('rounds/{round}/open', [PilotRoundController::class, 'open']);
        Route::post('rounds/{round}/close', [PilotRoundController::class, 'close']);
        // Banque de questions diffusable (sélection de manche, jamais correct_index).
        Route::get('questions', [PilotQuestionController::class, 'index']);
    });
});
