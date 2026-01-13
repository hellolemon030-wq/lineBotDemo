<?php
namespace App\Services\Laravel\BotFeatureModule;

interface replyEngineModuleInterface {
    static public function loadEventList($botId);
    static public function getBotModuleReplyEngine($botId,$initParams = '');
}