<?php

declare(strict_types=1);

use App\Filament\Admin\Pages\ThresholdsSettings;
use App\Filament\Admin\Resources\GameSessions\Pages\ListGameSessions;
use App\Filament\Admin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\Admin\Resources\Tenants\Pages\ListTenants;
use App\Models\GameSession;
use App\Models\Tenant;
use App\Models\TenantGameConfig;
use App\Models\User;
use App\Services\PairingCodeService;
use Illuminate\Database\QueryException;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Panel Filament « admin » : ressources, configuration de jeu, cycle de vie
|--------------------------------------------------------------------------
*/

beforeEach(function (): void {
    $this->admin = User::factory()->create();
});

// --- Accès -----------------------------------------------------------------

it('redirige les invités des ressources vers la page de connexion', function (string $url): void {
    $this->get($url)->assertRedirect('/admin/login');
})->with([
    'chaînes' => '/admin/tenants',
    'émissions' => '/admin/emissions',
    'sessions de jeu' => '/admin/game-sessions',
    'seuils et réglages' => '/admin/thresholds-settings',
]);

it('autorise un administrateur authentifié sur les ressources', function (string $url): void {
    $this->actingAs($this->admin)->get($url)->assertSuccessful();
})->with([
    'chaînes' => '/admin/tenants',
    'émissions' => '/admin/emissions',
    'sessions de jeu' => '/admin/game-sessions',
    'seuils et réglages' => '/admin/thresholds-settings',
]);

// --- Tenants et configuration de jeu ----------------------------------------

it('affiche la liste des chaînes', function (): void {
    $tenant = Tenant::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(ListTenants::class)
        ->assertCanSeeTableRecords([$tenant]);
});

it('crée une chaîne avec sa configuration de jeu (I-31, I-25, I-28, I-40)', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CreateTenant::class)
        ->fillForm([
            'name' => 'Chaîne Test',
            'slug' => 'chaine-test',
            'gameConfig' => [
                'answer_window_seconds' => 12,
                'measured_mode_enabled' => true,
                'endgame_rule' => TenantGameConfig::ENDGAME_DRAW,
                'winners_count' => 3,
                'persistent_ranking_enabled' => true,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $tenant = Tenant::query()->where('slug', 'chaine-test')->firstOrFail();

    expect($tenant->gameConfig)
        ->not->toBeNull()
        ->answer_window_seconds->toBe(12)
        ->measured_mode_enabled->toBeTrue()
        ->endgame_rule->toBe(TenantGameConfig::ENDGAME_DRAW)
        ->winners_count->toBe(3)
        ->persistent_ranking_enabled->toBeTrue();
});

it('utilise le défaut paramétrable quand la fenêtre du tenant est nulle (I-31)', function (): void {
    $config = TenantGameConfig::factory()->create(['answer_window_seconds' => null]);

    expect($config->effectiveAnswerWindowSeconds())
        ->toBe((int) config('interflo.answer_window_seconds'));
});

// --- Contrainte scellée winners_count (I-28) --------------------------------

it('accepte les nombres de gagnants scellés 1, 3 et 5 (I-28)', function (int $winnersCount): void {
    $config = TenantGameConfig::factory()->drawEndgame($winnersCount)->create();

    expect($config->winners_count)->toBe($winnersCount);
})->with([1, 3, 5]);

it('rejette en base un nombre de gagnants hors options scellées (I-28)', function (): void {
    TenantGameConfig::factory()->drawEndgame(2)->create();
})->throws(QueryException::class);

it('rejette en validation un nombre de gagnants hors options scellées (I-28)', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CreateTenant::class)
        ->fillForm([
            'name' => 'Chaîne Refus',
            'slug' => 'chaine-refus',
            'gameConfig' => [
                'endgame_rule' => TenantGameConfig::ENDGAME_DRAW,
                'winners_count' => 4,
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['gameConfig.winners_count']);
});

// --- Cycle de vie des sessions (I-16) ----------------------------------------

it('démarre une session programmée (scheduled → live)', function (): void {
    $session = GameSession::factory()->scheduled()->create();

    Livewire::actingAs($this->admin)
        ->test(ListGameSessions::class)
        ->assertTableActionVisible('start', $session)
        ->callTableAction('start', $session);

    $session->refresh();

    expect($session->status)->toBe(GameSession::STATUS_LIVE)
        ->and($session->started_at)->not->toBeNull();
});

it('clôture une session en direct (live → ended)', function (): void {
    $session = GameSession::factory()->create(); // live par défaut

    Livewire::actingAs($this->admin)
        ->test(ListGameSessions::class)
        ->assertTableActionVisible('end', $session)
        ->callTableAction('end', $session);

    $session->refresh();

    expect($session->status)->toBe(GameSession::STATUS_ENDED)
        ->and($session->ended_at)->not->toBeNull();
});

it('n\'offre pas les actions de cycle de vie sur une session terminée (I-16)', function (): void {
    $session = GameSession::factory()->ended()->create();

    Livewire::actingAs($this->admin)
        ->test(ListGameSessions::class)
        ->assertTableActionHidden('start', $session)
        ->assertTableActionHidden('end', $session)
        ->assertTableActionHidden('rotate_pairing_code', $session);
});

it('régénère le code d\'appairage : l\'ancien meurt, un nouveau naît (I-18)', function (): void {
    $session = GameSession::factory()->create();
    $oldCode = app(PairingCodeService::class)->createCode($session);

    Livewire::actingAs($this->admin)
        ->test(ListGameSessions::class)
        ->assertTableActionVisible('rotate_pairing_code', $session)
        ->callTableAction('rotate_pairing_code', $session);

    $oldCode->refresh();

    expect($oldCode->valid_until->isPast())->toBeTrue()
        ->and($session->currentPairingCode())->not->toBeNull()
        ->and($session->currentPairingCode()->id)->not->toBe($oldCode->id);
});

// --- Page « Seuils et réglages » (lecture seule) ------------------------------

it('affiche les valeurs scellées et paramétrables actives', function (): void {
    Livewire::actingAs($this->admin)
        ->test(ThresholdsSettings::class)
        ->assertSee((string) config('interflo.sealed.propositions_per_question'))
        ->assertSee((string) config('interflo.answer_window_seconds'))
        ->assertSee('I-28');
});
