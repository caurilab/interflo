<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Questions\Tables;

use App\Models\Question;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('theme.title')
                    ->label(__('panel.questions.fields.theme'))
                    ->placeholder(__('panel.questions.in_bank'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('round_number')
                    ->label(__('panel.questions.fields.round_number'))
                    ->sortable(),
                TextColumn::make('body')
                    ->label(__('panel.questions.fields.body'))
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('source')
                    ->label(__('panel.questions.fields.source'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('panel.questions.sources.'.$state))
                    ->color(fn (string $state): array => match ($state) {
                        Question::SOURCE_PLATEAU => Color::Fuchsia,
                        default => Color::Sky,
                    }),
                // EX-40 : badge « NON VALIDÉE » tant que pas de validation
                // humaine — la question n'est pas diffusable.
                TextColumn::make('validated_at')
                    ->label(__('panel.questions.fields.validation'))
                    ->badge()
                    ->state(fn (Question $record): string => $record->isValidated()
                        ? __('panel.questions.validated')
                        : __('panel.questions.not_validated'))
                    ->color(fn (Question $record): array => $record->isValidated()
                        ? Color::Green
                        : Color::Red),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // EX-40 : action « Valider » réservée et explicite — trace
                // qui a validé et quand (I-33).
                Action::make('validate')
                    ->label(__('panel.questions.actions.validate'))
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color(Color::Green)
                    ->requiresConfirmation()
                    ->visible(fn (Question $record): bool => ! $record->isValidated())
                    ->action(function (Question $record): void {
                        $record->update([
                            'validated_at' => now(),
                            'validated_by' => auth()->id(),
                        ]);

                        Notification::make()->success()
                            ->title(__('panel.questions.actions.validated'))
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
