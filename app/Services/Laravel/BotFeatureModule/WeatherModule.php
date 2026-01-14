<?php
namespace App\Services\Laravel\BotFeatureModule;

use App\Services\LineBot\ReplyEngine;

class WeatherModule extends ModuleBase
{
    /**
     * Human-readable module name.
     */
    static public function getModuleName(): string
    {
        return 'WeatherModule';
    }

    /**
     * Indicates that this module can be used by the AutoReply Engine
     * and can be bound to keyword-based reply rules.
     */
    static public function _isAllowModuleReply(): bool
    {
        return true;
    }

    /**
     * Supported areas for this demo module.
     * The key is used as a unique event identifier,
     * and the value is shown to administrators.
     */
    static public array $areaList = [
        'tokyo' => 'Tokyo Weather',
        'osaka' => 'Osaka Weather',
    ];

    /**
     * Create a ReplyEngine instance for this module.
     *
     * $initParams usually comes from the matched rule configuration
     * (e.g. selected area or event identifier).
     *
     * @param string $botId
     * @param string $initParams
     * @return ReplyEngine|null
     */
    static public function getBotModuleReplyEngine(
        $botId,
        $initParams = ''
    ): ?ReplyEngine {
        return WeatherModuleReplyEngine::getReplyEngine($initParams);
    }

    /**
     * Load all available events for this module and bot.
     *
     * These events can be listed in the management console
     * and bound to keyword matching rules.
     *
     * @param string $botId
     * @return ModuleEvent[]|null
     */
    static public function loadEventList($botId): ?array
    {
        $events = [];

        foreach (self::$areaList as $uniqueId => $humanReadable) {
            $events[] = new ModuleEvent(
                $botId,
                static::getModuleTag(),
                $uniqueId,
                $humanReadable
            );
        }

        return $events;
    }
}