<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GameSessions\Tables;

use App\Models\GameSession;
use App\Services\GameSessionLifecycleService;
use App\Services\PairingCodeService;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GameSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('emission.title')
                    ->label(__('panel.game_sessions.fields.emission'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('panel.game_sessions.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('panel.game_sessions.status.'.$state))
                    ->color(fn (string $state): array => match ($state) {
                        GameSession::STATUS_LIVE => Color::Green,
                        GameSession::STATUS_ENDED => Color::Gray,
                        default => Color::Amber,
                    })
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label(__('panel.game_sessions.fields.started_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->label(__('panel.game_sessions.fields.ended_at'))
                    ->dateTime()
                    ->sortable(),
                // Code d'appairage courant, lecture seule (I-18 : rotatif).
                TextColumn::make('current_pairing_code')
                    ->label(__('panel.game_sessions.fields.pairing_code'))
                    ->state(fn (GameSession $record): string => $record->currentPairingCode()?->code
                        ?? __('panel.game_sessions.fields.no_pairing_code')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                // Cycle de vie : la session vit et meurt avec l'émission (I-16).
                Action::make('start')
                    ->label(__('panel.game_sessions.actions.start'))
                    ->icon(Heroicon::OutlinedPlay)
                    ->color(Color::Green)
                    ->requiresConfirmation()
                    ->visible(fn (GameSession $record): bool => $record->status === GameSession::STATUS_SCHEDULED)
                    ->action(function (GameSession $record, GameSessionLifecycleService $lifecycle): void {
                        try {
                            $lifecycle->start($record);
                            Notification::make()->success()
                                ->title(__('panel.game_sessions.actions.started'))
                                ->send();
                        } catch (DomainException) {
                            Notification::make()->danger()->send();
                        }
                    }),
                Action::make('end')
                    ->label(__('panel.game_sessions.actions.end'))
                    ->icon(Heroicon::OutlinedStop)
                    ->color(Color::Red)
                    ->requiresConfirmation()
                    ->visible(fn (GameSession $record): bool => $record->status === GameSession::STATUS_LIVE)
                    ->action(function (GameSession $record, GameSessionLifecycleService $lifecycle): void {
                        try {
                            $lifecycle->end($record);
                            Notification::make()->success()
                                ->title(__('panel.game_sessions.actions.ended'))
                                ->send();
                        } catch (DomainException) {
                            Notification::make()->danger()->send();
                        }
                    }),
                // Régénération manuelle du code d'appairage (I-18) : le code
                // courant meurt, un nouveau naît (PairingCodeService).
                Action::make('rotate_pairing_code')
                    ->label(__('panel.game_sessions.actions.rotate_code'))
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->requiresConfirmation()
                    ->visible(fn (GameSession $record): bool => ! $record->hasEnded())
                    ->action(function (GameSession $record, PairingCodeService $pairingCodes): void {
                        $pairingCodes->rotate($record);
                        Notification::make()->success()
                            ->title(__('panel.game_sessions.actions.code_rotated'))
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
