<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tenants\Schemas;

use App\Models\TenantGameConfig;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

/**
 * Formulaire Tenant : identité de la chaîne + configuration de jeu (I-31,
 * I-25, I-28, I-40) éditée en ligne via la relation HasOne.
 */
class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('panel.tenants.sections.identity'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('panel.tenants.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label(__('panel.tenants.fields.slug'))
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('external_reference')
                            ->label(__('panel.tenants.fields.external_reference'))
                            ->maxLength(255),
                    ]),

                // Configuration de jeu (docs/07 §2.1 — table PROVISOIRE).
                Section::make(__('panel.tenants.sections.game_config'))
                    ->relationship('gameConfig')
                    ->schema([
                        TextInput::make('answer_window_seconds')
                            ->label(__('panel.game_config.answer_window_seconds'))
                            ->helperText(__('panel.game_config.answer_window_seconds_helper'))
                            ->numeric()
                            ->minValue(1)
                            // null = défaut paramétrable (I-31).
                            ->nullable(),
                        Toggle::make('measured_mode_enabled')
                            ->label(__('panel.game_config.measured_mode_enabled'))
                            ->helperText(__('panel.game_config.measured_mode_helper'))
                            ->default(false),
                        Select::make('endgame_rule')
                            ->label(__('panel.game_config.endgame_rule'))
                            ->options([
                                TenantGameConfig::ENDGAME_ALL_SURVIVORS => __('panel.game_config.endgame_rule_all_survivors'),
                                TenantGameConfig::ENDGAME_DRAW => __('panel.game_config.endgame_rule_draw'),
                            ])
                            ->default(TenantGameConfig::ENDGAME_ALL_SURVIVORS)
                            ->required()
                            ->live(),
                        // ⚠️ Conséquence réglementaire (§10.4 du cadrage) :
                        // avertissement visible quand le tirage au sort est choisi.
                        Placeholder::make('endgame_rule_draw_warning')
                            ->label('')
                            ->content(fn (): HtmlString => new HtmlString(
                                '<span class="text-danger-600 dark:text-danger-400 font-medium">'
                                .e(__('panel.game_config.endgame_rule_draw_warning'))
                                .'</span>'
                            ))
                            ->visible(fn (Get $get): bool => $get('endgame_rule') === TenantGameConfig::ENDGAME_DRAW),
                        Select::make('winners_count')
                            ->label(__('panel.game_config.winners_count'))
                            ->helperText(__('panel.game_config.winners_count_helper'))
                            // Options scellées par décision PO (I-28) : 1, 3 ou 5.
                            ->options(fn (): array => array_combine(
                                config('interflo.sealed.winner_count_options'),
                                config('interflo.sealed.winner_count_options'),
                            ))
                            ->rule(Rule::in(config('interflo.sealed.winner_count_options')))
                            ->nullable()
                            ->visible(fn (Get $get): bool => $get('endgame_rule') === TenantGameConfig::ENDGAME_DRAW)
                            ->required(fn (Get $get): bool => $get('endgame_rule') === TenantGameConfig::ENDGAME_DRAW),
                        Toggle::make('persistent_ranking_enabled')
                            ->label(__('panel.game_config.persistent_ranking_enabled'))
                            ->helperText(__('panel.game_config.persistent_ranking_helper'))
                            ->default(false),
                    ]),
            ]);
    }
}
