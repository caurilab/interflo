<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tenants\Tables;

use App\Models\TenantGameConfig;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('panel.tenants.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('panel.tenants.fields.slug'))
                    ->searchable(),
                TextColumn::make('external_reference')
                    ->label(__('panel.tenants.fields.external_reference'))
                    ->toggleable(isToggledHiddenByDefault: true),
                // Fenêtre effective : valeur du tenant ou défaut paramétrable (I-31).
                TextColumn::make('gameConfig.answer_window_seconds')
                    ->label(__('panel.game_config.answer_window_seconds'))
                    ->placeholder((string) config('interflo.answer_window_seconds')),
                IconColumn::make('gameConfig.measured_mode_enabled')
                    ->label(__('panel.game_config.measured_mode_enabled'))
                    ->boolean(),
                TextColumn::make('gameConfig.endgame_rule')
                    ->label(__('panel.game_config.endgame_rule'))
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        TenantGameConfig::ENDGAME_DRAW => __('panel.game_config.endgame_rule_draw'),
                        default => __('panel.game_config.endgame_rule_all_survivors'),
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
