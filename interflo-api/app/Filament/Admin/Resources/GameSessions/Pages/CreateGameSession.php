<?php

namespace App\Filament\Admin\Resources\GameSessions\Pages;

use App\Filament\Admin\Resources\GameSessions\GameSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGameSession extends CreateRecord
{
    protected static string $resource = GameSessionResource::class;
}
