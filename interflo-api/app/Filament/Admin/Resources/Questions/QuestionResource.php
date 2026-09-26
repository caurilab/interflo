<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Questions;

use App\Filament\Admin\Resources\Questions\Pages\CreateQuestion;
use App\Filament\Admin\Resources\Questions\Pages\EditQuestion;
use App\Filament\Admin\Resources\Questions\Pages\ListQuestions;
use App\Filament\Admin\Resources\Questions\Schemas\QuestionForm;
use App\Filament\Admin\Resources\Questions\Tables\QuestionsTable;
use App\Models\Question;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Banque de questions (§15.3 cadrage) : seul endroit où correct_index est
 * visible — c'est le back-office. ⚠️ INV-2 : il ne sort jamais par l'API.
 *
 * EX-40 : tant qu'une question n'est pas validée par un humain (action
 * « Valider » explicite), elle porte le badge « NON VALIDÉE » et ne peut pas
 * être attachée à une manche (contrôle côté service, EliminationThemeService).
 */
class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    // Labels et navigation via les fichiers de langue (docs/CONVENTIONS.md §2).
    public static function getModelLabel(): string
    {
        return __('panel.questions.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('panel.questions.plural_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel.nav_group_game');
    }

    public static function form(Schema $schema): Schema
    {
        return QuestionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuestionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuestions::route('/'),
            'create' => CreateQuestion::route('/create'),
            'edit' => EditQuestion::route('/{record}/edit'),
        ];
    }
}
