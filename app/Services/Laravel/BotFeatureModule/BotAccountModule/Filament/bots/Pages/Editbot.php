<?php

namespace App\Services\Laravel\BotFeatureModule\BotAccountModule\Filament\bots\Pages;

use App\Services\Laravel\BotFeatureModule\BotAccountModule\Filament\bots\botResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class Editbot extends EditRecord
{
    protected static string $resource = botResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
