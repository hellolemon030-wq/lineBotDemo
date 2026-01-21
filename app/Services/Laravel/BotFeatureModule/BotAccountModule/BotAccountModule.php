<?php
namespace App\Services\Laravel\BotFeatureModule\BotAccountModule;

use App\Services\Laravel\BotFeatureModule\ModuleBase;

class BotAccountModule extends ModuleBase{
    static public function getModuleName(): string
    {
        return 'BotAccountModule';
    }

    static public function _needFilamentSupport()
    {
        return true;
    }

}