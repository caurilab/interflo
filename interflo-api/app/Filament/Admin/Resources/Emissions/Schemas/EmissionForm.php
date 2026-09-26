<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Emissions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Émission rattachée à une chaîne, copiée depuis BOS (I-36).
                Select::make('tenant_id')
                    ->label(__('panel.emissions.fields.tenant'))
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->label(__('panel.emissions.fields.title'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('external_reference')
                    ->label(__('panel.emissions.fields.external_reference'))
                    ->maxLength(255),
            ]);
    }
}
