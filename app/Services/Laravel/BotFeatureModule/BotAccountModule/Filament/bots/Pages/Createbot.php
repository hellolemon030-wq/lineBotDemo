<?php

namespace App\Services\Laravel\BotFeatureModule\BotAccountModule\Filament\bots\Pages;

use App\Services\Laravel\BotFeatureModule\BotAccountModule\Filament\bots\botResource;
use Filament\Resources\Pages\CreateRecord;

class Createbot extends CreateRecord
{
    protected static string $resource = botResource::class;
}
