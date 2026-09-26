<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\SmsSender;
use App\Services\Sms\LogSmsSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ⚠️ Aucun provider SMS n'est choisi : le contrat SmsSender est lié
        // à l'implémentation log. Brancher un vrai provider = changer cette
        // ligne uniquement.
        $this->app->bind(SmsSender::class, LogSmsSender::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Anti-abus sur la demande de code OTP, par numéro (I-8).
        // ⚠️ Point de départ non validé (10/heure).
        RateLimiter::for('otp-request', function (Request $request): Limit {
            return Limit::perHour((int) config('interflo.otp_request_throttle_per_hour', 10))
                ->by((string) $request->input('phone', $request->ip()));
        });

        // Filet additionnel sur la vérification (otp_max_attempts borne déjà
        // chaque challenge). ⚠️ Point de départ non validé (10/minute).
        RateLimiter::for('otp-verify', function (Request $request): Limit {
            return Limit::perMinute((int) config('interflo.otp_verify_throttle_per_minute', 10))
                ->by((string) $request->input('phone', $request->ip()));
        });
    }
}
