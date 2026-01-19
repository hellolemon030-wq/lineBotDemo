<?php
namespace App\Services\pegaservice;

use App\Services\Laravel\BotFeatureModule\ModuleBase;
use App\Services\Laravel\BotFeatureModule\ModuleEvent;
use App\Services\LineBot\ReplyEngine;

class PegaModule extends ModuleBase{
    static public function getModuleName(): string
    {
        return 'PegaModule';
    }

    static public function getModuleDescription(): string
    {
        return 'Pega Service demo Module';
    }

    static public function _isAllowModuleReply(): bool
    {
        return true;
    }

    static public function getBotModuleReplyEngine($botId, $initParams = ''): ?ReplyEngine
    {
        return parent::getBotModuleReplyEngine($botId, $initParams);
    }
    static public function loadEventList($botId): ?array
    {
        $ret = [];
        $ret[] = new ModuleEvent($botId,static::getModuleName(),'default','Pega Service default event');
        return $ret;
    }
}