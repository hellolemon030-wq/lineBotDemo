<?php

namespace App\Services\Laravel\BotFeatureModule\BotAccountModule\Filament\bots\Pages;

use App\Services\Laravel\BotFeatureModule\BotAccountModule\Filament\bots\botResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class Listbots extends ListRecords
{
    protected static string $resource = botResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
