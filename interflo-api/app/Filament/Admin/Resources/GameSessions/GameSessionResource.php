<?php

namespace App\Filament\Admin\Resources\GameSessions;

use App\Filament\Admin\Resources\GameSessions\Pages\CreateGameSession;
use App\Filament\Admin\Resources\GameSessions\Pages\EditGameSession;
use App\Filament\Admin\Resources\GameSessions\Pages\ListGameSessions;
use App\Filament\Admin\Resources\GameSessions\Schemas\GameSessionForm;
use App\Filament\Admin\Resources\GameSessions\Tables\GameSessionsTable;
use App\Models\GameSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GameSessionResource extends Resource
{
    protected static ?string $model = GameSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    // Labels et navigation via les fichiers de langue (docs/CONVENTIONS.md §2).
    public static function getModelLabel(): string
    {
        return __('panel.game_sessions.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel.game_sessions.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel.nav_group_game');
    }

    public static function form(Schema $schema): Schema
    {
        return GameSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameSessionsTable::configure($table);
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
            'index' => ListGameSessions::route('/'),
            'create' => CreateGameSession::route('/create'),
            'edit' => EditGameSession::route('/{record}/edit'),
        ];
    }
}
