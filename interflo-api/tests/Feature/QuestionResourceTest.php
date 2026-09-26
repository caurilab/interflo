<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\Questions\Pages\CreateQuestion;
use App\Filament\Admin\Resources\Questions\Pages\ListQuestions;
use App\Models\Question;
use App\Models\User;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Filament — QuestionResource : banque de questions (§15.3 cadrage)
|--------------------------------------------------------------------------
|
| EX-40 : aucune question ne part à l'antenne sans validation humaine. La
| validation est une action « Valider » réservée et explicite ; tant qu'elle
| n'a pas eu lieu, badge « NON VALIDÉE ». correct_index n'est visible QU'ICI
| (back-office) — INV-2.
|
*/

beforeEach(function (): void {
    $this->admin = User::factory()->create();
});

it('redirige les invités et autorise l’administrateur sur la banque de questions', function (): void {
    $this->get('/admin/questions')->assertRedirect('/admin/login');

    $this->actingAs($this->admin)->get('/admin/questions')->assertSuccessful();
});

/** Format de remplissage du Repeater « simple » : tableau de lignes. */
function propositionsFormData(array $values): array
{
    return array_map(fn (string $value): array => ['proposition' => $value], $values);
}

it('crée une question avec exactement 4 propositions (scellé I-4)', function (): void {
    Livewire::actingAs($this->admin)
        ->test(CreateQuestion::class)
        ->fillForm([
            'round_number' => 1,
            'body' => 'Quelle couleur portait l’invité ?',
            'propositions' => propositionsFormData(['Rouge', 'Bleu', 'Vert', 'Jaune']),
            'correct_index' => 2,
            'source' => Question::SOURCE_PLATEAU,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $question = Question::query()->sole();

    expect($question->propositions)->toHaveCount(4)
        ->and($question->correct_index)->toBe(2)
        // EX-40 : jamais validée à la création — validation = action explicite.
        ->and($question->validated_at)->toBeNull()
        ->and($question->validated_by)->toBeNull();
});

it('rejette une question sans exactement 4 propositions (scellé I-4)', function (array $propositions): void {
    Livewire::actingAs($this->admin)
        ->test(CreateQuestion::class)
        ->fillForm([
            'round_number' => 1,
            'body' => 'Question mal formée ?',
            'propositions' => propositionsFormData($propositions),
            'correct_index' => 0,
            'source' => Question::SOURCE_GENERAL_CULTURE,
        ])
        ->call('create')
        ->assertHasFormErrors(['propositions']);

    expect(Question::query()->count())->toBe(0);
})->with([
    'trois propositions' => [['A', 'B', 'C']],
    'cinq propositions' => [['A', 'B', 'C', 'D', 'E']],
]);

it('porte le badge NON VALIDÉE tant qu’aucun humain n’a validé (EX-40)', function (): void {
    $question = Question::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(ListQuestions::class)
        ->assertCanSeeTableRecords([$question])
        ->assertTableActionVisible('validate', $question)
        ->assertSee(__('panel.questions.not_validated'));
});

it('valide une question par une action réservée et explicite (EX-40 / I-33)', function (): void {
    $question = Question::factory()->create();

    Livewire::actingAs($this->admin)
        ->test(ListQuestions::class)
        ->callTableAction('validate', $question);

    $question->refresh();

    expect($question->isValidated())->toBeTrue()
        ->and($question->validated_by)->toBe($this->admin->id);

    // L'action disparaît une fois la question validée.
    Livewire::actingAs($this->admin)
        ->test(ListQuestions::class)
        ->assertTableActionHidden('validate', $question);
});
