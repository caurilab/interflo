<?php

namespace App\Filament\Admin\Resources\GameSessions\Pages;

use App\Filament\Admin\Resources\GameSessions\GameSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGameSession extends EditRecord
{
    protected static string $resource = GameSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
