<?php
namespace App\Services\Laravel\BotFeatureModule;

use App\Services\LineBot\LineMessage;
use App\Services\LineBot\lineReplyMessage;
use App\Services\LineBot\ReplyEngine;
use Illuminate\Support\Facades\Http;

class WeatherModuleReplyEngine implements ReplyEngine{
    static public $areaParams = [
        'tokyo' => [
            'x' => 35.6895,
            'y' => 139.6917,
            'label' => '东京',
        ],
        'osaka' => [
            'x' => 34.6937,
            'y' => 135.5023,
            'label' => '大阪',
        ],
    ];

    public $initArea = 'tokyo';

    static public function getReplyEngine($area = ''){
        $instance = new self();
        if(key_exists($area,static::$areaParams)){
            $instance->initArea = $area;
        } else {
            $instance->initArea = 'tokyo';
        }
        return $instance;
    }

    /**
     * @param $lineMessage LineMessage;
     */
    public function handle(LineMessage $lineMessage, lineReplyMessage &$lineReplyMessage)
    {
        $queryParams = $this->getXYByAreaName($this->initArea);
        $result = $this->queryResult($queryParams['x'],$queryParams['y']);

        if (
            empty($result) ||
            !isset($result['current_weather'])
        ) {
            $lineReplyMessage->appendText('Weather service is temporarily unavailable.');
            return;
        }

        $weather = $result['current_weather'];

        $temperature = $weather['temperature'] ?? 'N/A';
        $windSpeed   = $weather['windspeed'] ?? 'N/A';
        $weatherText = $this->weatherCodeToText($weather['weathercode'] ?? -1);

        $replyText = sprintf(
            "🌤 Weather Update (%s)\nTemperature: %s°C\nWind Speed: %s km/h\nCondition: %s",
            strtoupper($this->initArea),
            $temperature,
            $windSpeed,
            $weatherText
        );

        $lineReplyMessage->appendText($replyText);
        return $lineReplyMessage;
    }

    public function getXYByAreaName($areaName){
        return array_key_exists($areaName,static::$areaParams) ? 
            static::$areaParams[$areaName] :
            static::$areaParams['tokyo'];
    }

    public function queryResult($x,$y){
        //https://api.open-meteo.com/v1/forecast?latitude=35.6895&longitude=139.6917&current_weather=true
        $response = Http::get('https://api.open-meteo.com/v1/forecast', [
            'latitude' => $x,
            'longitude' => $y,
            'current_weather' => true,
        ]);

        $data = $response->json();
        return $data;
    }

    protected function weatherCodeToText(int $code): string
    {
        return match ($code) {
            0 => 'Clear sky',
            1, 2 => 'Partly cloudy',
            3 => 'Cloudy',
            45, 48 => 'Fog',
            51, 53, 55 => 'Drizzle',
            61, 63, 65 => 'Rain',
            71, 73, 75 => 'Snow',
            default => 'Unknown weather',
        };
    }
}