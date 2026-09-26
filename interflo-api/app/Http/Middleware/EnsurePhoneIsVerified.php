<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Player;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige un joueur authentifié au numéro de téléphone vérifié (I-8).
 */
class EnsurePhoneIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof Player || ! $user->hasVerifiedPhone()) {
            abort(403, __('interflo.phone_not_verified'));
        }

        return $next($request);
    }
}
