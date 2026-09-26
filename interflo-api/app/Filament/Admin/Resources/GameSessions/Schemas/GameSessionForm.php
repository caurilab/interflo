<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\GameSessions\Schemas;

use App\Models\GameSession;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class GameSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                // La session vit et meurt avec l'émission (I-16).
                Select::make('emission_id')
                    ->label(__('panel.game_sessions.fields.emission'))
                    ->relationship('emission', 'title')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('status')
                    ->label(__('panel.game_sessions.fields.status'))
                    ->options([
                        GameSession::STATUS_SCHEDULED => __('panel.game_sessions.status.scheduled'),
                        GameSession::STATUS_LIVE => __('panel.game_sessions.status.live'),
                        GameSession::STATUS_ENDED => __('panel.game_sessions.status.ended'),
                    ])
                    ->default(GameSession::STATUS_SCHEDULED)
                    ->required(),
                DateTimePicker::make('started_at')
                    ->label(__('panel.game_sessions.fields.started_at'))
                    ->nullable(),
                DateTimePicker::make('ended_at')
                    ->label(__('panel.game_sessions.fields.ended_at'))
                    ->nullable(),
                // Code d'appairage courant, en lecture seule (I-18 : rotatif).
                Placeholder::make('current_pairing_code')
                    ->label(__('panel.game_sessions.fields.pairing_code'))
                    ->content(fn (?GameSession $record): string => $record?->currentPairingCode()?->code
                        ?? __('panel.game_sessions.fields.no_pairing_code'))
                    ->visibleOn('edit'),
                // ⚠️ Auth animateur PROVISOIRE : token de pilotage de la
                // console tablette (en-tête X-Pilot-Token), en lecture seule.
                // Non spécifié par les documents — voir EnsurePilotToken.
                Placeholder::make('pilot_token')
                    ->label(__('panel.game_sessions.fields.pilot_token'))
                    ->content(fn (?GameSession $record): string => $record?->pilot_token ?? '—')
                    ->visibleOn('edit'),
            ]);
    }
}
