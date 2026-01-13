<?php
namespace App\Services\Laravel\BotFeatureModule;

use App\Services\LineBot\LineMessage;
use App\Services\LineBot\lineReplyMessage;
use App\Services\LineBot\ReplyEngine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherModule extends ModuleBase{

    static public $areaList = [
        'tokyo' => 'Tokyo Weather',
        'osaka' => 'Osaka Weather',
    ];

    static public function getBotModuleReplyEngine($botId, $initParams = '')
    {
        $area = '';
        $initParams = json_decode($initParams,true);
        if(array_key_exists('area',$initParams)){
            $area = $initParams['area'];
        }
        return WeatherModuleReplyEngine::getReplyEngine($area);
    }

    static public function loadEventList($botId)
    {
        $events = [];
        foreach (self::$areaList as $uniqueId => $human) {
            $events[] = new ModuleEvent($botId, 'WeatherModule', $uniqueId, $human);
        }
        return $events;
    }
}