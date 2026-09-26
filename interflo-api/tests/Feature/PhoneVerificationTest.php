<?php

declare(strict_types=1);

use App\Contracts\SmsSender;
use App\Models\PhoneVerificationChallenge;
use App\Models\Player;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Vérification du numéro de téléphone (I-8)
|--------------------------------------------------------------------------
|
| Flux OTP complet : demande de code, vérification, non-énumération,
| throttling. Le SMS est capturé via un SmsSender factice.
|
*/

beforeEach(function (): void {
    // SmsSender factice : capture les messages au lieu de les envoyer.
    $this->app->instance(SmsSender::class, new class implements SmsSender
    {
        /** @var array<int, array{phone: string, message: string}> */
        public array $sent = [];

        public function send(string $phone, string $message): void
        {
            $this->sent[] = ['phone' => $phone, 'message' => $message];
        }
    });
});

/** Extrait le code OTP du SMS capturé. */
function capturedOtpCode(SmsSender $sender): string
{
    expect($sender->sent)->not->toBeEmpty();
    preg_match('/\b(\d{6})\b/', end($sender->sent)['message'], $matches);

    return $matches[1];
}

it('crée un challenge et envoie le code par SMS, sans jamais stocker le code en clair', function (): void {
    $response = $this->postJson('/api/v1/players/phone/request-code', ['phone' => '+33612345678']);

    $response->assertNoContent();

    $challenge = PhoneVerificationChallenge::query()->sole();
    expect($challenge->phone)->toBe('+33612345678')
        ->and($challenge->attempts)->toBe(0)
        ->and($challenge->consumed_at)->toBeNull()
        ->and($challenge->expires_at->isFuture())->toBeTrue()
        // Le hash ne contient pas le code, mais le vérifie.
        ->and(Hash::check(capturedOtpCode($this->app->make(SmsSender::class)), $challenge->code_hash))->toBeTrue()
        ->and($challenge->code_hash)->not->toContain(capturedOtpCode($this->app->make(SmsSender::class)));
});

it('rejette un numéro qui n’est pas au format E.164', function (string $phone): void {
    $this->postJson('/api/v1/players/phone/request-code', ['phone' => $phone])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('phone');
})->with([
    'sans indicatif' => ['0612345678'],
    'zéro après le plus' => ['+0612345678'],
    'lettres' => ['+33ABCDEFGH'],
    'vide' => [''],
]);

it('vérifie le code, crée le joueur et renvoie un token Sanctum', function (): void {
    $this->postJson('/api/v1/players/phone/request-code', ['phone' => '+33612345678']);
    $code = capturedOtpCode($this->app->make(SmsSender::class));

    $response = $this->postJson('/api/v1/players/phone/verify', [
        'phone' => '+33612345678',
        'code' => $code,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'player' => ['id', 'phone', 'phone_verified_at']]);

    $player = Player::query()->sole();
    expect($player->phone)->toBe('+33612345678')
        ->and($player->hasVerifiedPhone())->toBeTrue()
        ->and(PhoneVerificationChallenge::query()->sole()->consumed_at)->not->toBeNull();

    // Le token fonctionne réellement contre une route protégée.
    $this->withToken($response->json('token'))
        ->deleteJson('/api/v1/sessions/attach')
        ->assertNoContent();
});

it('réutilise le joueur existant sans créer de doublon', function (): void {
    $player = Player::factory()->unverified()->create(['phone' => '+33612345678']);

    $this->postJson('/api/v1/players/phone/request-code', ['phone' => '+33612345678']);
    $code = capturedOtpCode($this->app->make(SmsSender::class));

    $this->postJson('/api/v1/players/phone/verify', ['phone' => '+33612345678', 'code' => $code])
        ->assertOk()
        ->assertJsonPath('player.id', $player->id);

    expect(Player::query()->count())->toBe(1)
        ->and($player->fresh()->hasVerifiedPhone())->toBeTrue();
});

it('rejette un code faux et compte la tentative', function (): void {
    $this->postJson('/api/v1/players/phone/request-code', ['phone' => '+33612345678']);
    $code = capturedOtpCode($this->app->make(SmsSender::class));
    $wrongCode = $code === '000000' ? '000001' : '000000';

    $this->postJson('/api/v1/players/phone/verify', ['phone' => '+33612345678', 'code' => $wrongCode])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');

    expect(PhoneVerificationChallenge::query()->sole()->attempts)->toBe(1);
    expect(Player::query()->count())->toBe(0);
});

it('rejette un code expiré', function (): void {
    $this->postJson('/api/v1/players/phone/request-code', ['phone' => '+33612345678']);
    $code = capturedOtpCode($this->app->make(SmsSender::class));

    // Au-delà de la TTL configurée (point de départ : 300 s).
    $this->travel((int) config('interflo.otp_ttl_seconds') + 1)->seconds();

    $this->postJson('/api/v1/players/phone/verify', ['phone' => '+33612345678', 'code' => $code])
        ->assertUnprocessable();

    expect(Player::query()->count())->toBe(0);
});

it('rejette après épuisement des tentatives, même avec le bon code', function (): void {
    $this->postJson('/api/v1/players/phone/request-code', ['phone' => '+33612345678']);
    $code = capturedOtpCode($this->app->make(SmsSender::class));
    $wrongCode = $code === '000000' ? '000001' : '000000';
    $maxAttempts = (int) config('interflo.otp_max_attempts');

    for ($i = 0; $i < $maxAttempts; $i++) {
        $this->postJson('/api/v1/players/phone/verify', ['phone' => '+33612345678', 'code' => $wrongCode])
            ->assertUnprocessable();
    }

    // Le bon code arrive trop tard : le challenge est mort.
    $this->postJson('/api/v1/players/phone/verify', ['phone' => '+33612345678', 'code' => $code])
        ->assertUnprocessable();

    expect(Player::query()->count())->toBe(0);
});

it('renvoie une réponse identique que le numéro soit connu ou non (non-énumération)', function (): void {
    Player::factory()->create(['phone' => '+33600000001']);

    // Demande de code : même 204, même corps vide.
    $known = $this->postJson('/api/v1/players/phone/request-code', ['phone' => '+33600000001']);
    $unknown = $this->postJson('/api/v1/players/phone/request-code', ['phone' => '+33600000002']);

    expect($known->getStatusCode())->toBe($unknown->getStatusCode())
        ->and($known->getContent())->toBe($unknown->getContent());

    // Vérification : même 422, même message pour numéro sans challenge et mauvais code.
    $this->postJson('/api/v1/players/phone/request-code', ['phone' => '+33612345678']);

    $noChallenge = $this->postJson('/api/v1/players/phone/verify', ['phone' => '+33699999999', 'code' => '123456']);
    $wrongCode = $this->postJson('/api/v1/players/phone/verify', ['phone' => '+33612345678', 'code' => '999998']);

    expect($noChallenge->getStatusCode())->toBe(422)
        ->and($wrongCode->getStatusCode())->toBe(422)
        ->and($noChallenge->json('errors.code'))->toBe($wrongCode->json('errors.code'));
});

it('limite le nombre de demandes de code par numéro et par heure', function (): void {
    $limit = (int) config('interflo.otp_request_throttle_per_hour');

    for ($i = 0; $i < $limit; $i++) {
        $this->postJson('/api/v1/players/phone/request-code', ['phone' => '+33612345678'])
            ->assertNoContent();
    }

    $this->postJson('/api/v1/players/phone/request-code', ['phone' => '+33612345678'])
        ->assertTooManyRequests();
});

it('limite le nombre de tentatives de vérification par numéro et par minute', function (): void {
    $limit = (int) config('interflo.otp_verify_throttle_per_minute');

    for ($i = 0; $i < $limit; $i++) {
        $this->postJson('/api/v1/players/phone/verify', ['phone' => '+33612345678', 'code' => '123456'])
            ->assertUnprocessable();
    }

    $this->postJson('/api/v1/players/phone/verify', ['phone' => '+33612345678', 'code' => '123456'])
        ->assertTooManyRequests();
});
