<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Questions\Schemas;

use App\Models\Question;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Thème optionnel : la question peut rester en banque sans
                // thème, puis être rattachée quand l'animateur la programme.
                Select::make('theme_id')
                    ->label(__('panel.questions.fields.theme'))
                    ->relationship('theme', 'title')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                // Manche visée : 1..5 (scellé I-27).
                Select::make('round_number')
                    ->label(__('panel.questions.fields.round_number'))
                    ->options(array_combine(
                        range(1, (int) config('interflo.sealed.elimination_rounds')),
                        range(1, (int) config('interflo.sealed.elimination_rounds')),
                    ))
                    ->required(),
                Textarea::make('body')
                    ->label(__('panel.questions.fields.body'))
                    ->required()
                    ->columnSpanFull(),
                // Exactement 4 propositions (scellé I-4) — min=max=4.
                Repeater::make('propositions')
                    ->label(__('panel.questions.fields.propositions'))
                    ->simple(
                        TextInput::make('proposition')
                            ->label(__('panel.questions.fields.proposition'))
                            ->required(),
                    )
                    ->minItems((int) config('interflo.sealed.propositions_per_question'))
                    ->maxItems((int) config('interflo.sealed.propositions_per_question'))
                    ->defaultItems((int) config('interflo.sealed.propositions_per_question'))
                    ->required()
                    ->columnSpanFull(),
                // ⚠️ INV-2 : la bonne réponse n'est visible QU'ICI
                // (back-office) — jamais servie par l'API (CA-07).
                Select::make('correct_index')
                    ->label(__('panel.questions.fields.correct_index'))
                    ->options([
                        0 => __('panel.questions.fields.proposition_n', ['n' => 1]),
                        1 => __('panel.questions.fields.proposition_n', ['n' => 2]),
                        2 => __('panel.questions.fields.proposition_n', ['n' => 3]),
                        3 => __('panel.questions.fields.proposition_n', ['n' => 4]),
                    ])
                    ->required(),
                // Provenance (I-32) : plateau ou culture générale.
                Select::make('source')
                    ->label(__('panel.questions.fields.source'))
                    ->options([
                        Question::SOURCE_PLATEAU => __('panel.questions.sources.plateau'),
                        Question::SOURCE_GENERAL_CULTURE => __('panel.questions.sources.general_culture'),
                    ])
                    ->default(Question::SOURCE_PLATEAU)
                    ->required(),
                // Validation humaine (EX-40) : lecture seule — elle se pose par
                // l'action « Valider » explicite, jamais au formulaire.
                Placeholder::make('validation_state')
                    ->label(__('panel.questions.fields.validation'))
                    ->content(fn (?Question $record): string => $record?->isValidated()
                        ? __('panel.questions.validated_by_at', [
                            'name' => $record->validator?->name ?? '—',
                            'at' => $record->validated_at?->format('d/m/Y H:i'),
                        ])
                        : __('panel.questions.not_validated'))
                    ->visibleOn('edit'),
            ]);
    }
}
