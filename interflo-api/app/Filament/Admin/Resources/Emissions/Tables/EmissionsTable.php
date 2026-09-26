<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Emissions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tenant.name')
                    ->label(__('panel.emissions.fields.tenant'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('title')
                    ->label(__('panel.emissions.fields.title'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('external_reference')
                    ->label(__('panel.emissions.fields.external_reference'))
                    ->toggleable(isToggledHiddenByDefault: true),
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
