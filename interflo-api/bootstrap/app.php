<?php

use App\Exceptions\AnswerRejectedException;
use App\Http\Middleware\EnsurePhoneIsVerified;
use App\Http\Middleware\EnsurePilotToken;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            // Exige un joueur au numéro de téléphone vérifié (I-8).
            'phone.verified' => EnsurePhoneIsVerified::class,
            // ⚠️ Auth animateur PROVISOIRE : token opaque par session,
            // en-tête X-Pilot-Token. Non spécifiée par les documents —
            // à remplacer dès arbitrage (voir EnsurePilotToken).
            'pilot.token' => EnsurePilotToken::class,
        ]);

        // Les invités ne sont jamais redirigés : l'API répond 401 en JSON
        // (il n'existe pas de route « login » côté API) et le panel Filament
        // gère sa propre redirection d'authentification.
        $middleware->redirectGuestsTo(fn (Request $request) => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Rejet serveur d'une réponse joueur : JSON {message, error} avec le
        // code stable consommé par mobile et web, et server_time (EX-16).
        $exceptions->render(function (AnswerRejectedException $e) {
            return response()->json([
                'message' => __($e->translationKey),
                'error' => $e->errorCode,
                'server_time' => now()->toISOString(),
            ], $e->httpStatus);
        });
    })->create();
