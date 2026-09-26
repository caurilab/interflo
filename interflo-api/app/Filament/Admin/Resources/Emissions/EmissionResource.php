<?php

namespace App\Filament\Admin\Resources\Emissions;

use App\Filament\Admin\Resources\Emissions\Pages\CreateEmission;
use App\Filament\Admin\Resources\Emissions\Pages\EditEmission;
use App\Filament\Admin\Resources\Emissions\Pages\ListEmissions;
use App\Filament\Admin\Resources\Emissions\Schemas\EmissionForm;
use App\Filament\Admin\Resources\Emissions\Tables\EmissionsTable;
use App\Models\Emission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EmissionResource extends Resource
{
    protected static ?string $model = Emission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTv;

    // Labels et navigation via les fichiers de langue (docs/CONVENTIONS.md §2).
    public static function getModelLabel(): string
    {
        return __('panel.emissions.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel.emissions.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel.nav_group_game');
    }

    public static function form(Schema $schema): Schema
    {
        return EmissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmissionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmissions::route('/'),
            'create' => CreateEmission::route('/create'),
            'edit' => EditEmission::route('/{record}/edit'),
        ];
    }
}
