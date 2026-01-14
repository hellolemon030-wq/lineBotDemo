<?php
namespace App\Services\Laravel\BotFeatureModule\DemoModule;

use App\Services\Laravel\BotFeatureModule\ModuleBase;
use App\Services\Laravel\BotFeatureModule\ModuleEvent;
use App\Services\LineBot\ReplyEngine;

class DemoModule extends ModuleBase
{
    /**
     * Register this module to the system.
     * ModuleManager will discover available modules via this method.
     *
     * See LineBotAppProvider for the registration flow.
     */
    public static function getModuleName(): string
    {
        return 'DemoModule';
    }

    /**
     * Indicates whether this module can be used as a reply handler
     * in MatchRule configuration.
     *
     * If enabled, messages matched by keywords can be delegated
     * to this module for further processing.
     */
    public static function _isAllowModuleReply(): bool
    {
        return true;
    }

    /**
     * Load available events for this module under the specified bot.
     *
     * In a real-world scenario:
     * - An administrator logs into the management system
     * - Selects a bot they have permission to manage
     * - Creates multiple module-specific events (e.g. campaigns)
     *
     * This method exposes those events to the reply-rule system,
     * so that keywords can be bound to a specific module event.
     *
     * Below is mock data.
     * The actual implementation would query a database, e.g.:
     *   SELECT * FROM demo_config_tb WHERE bot_id = {botId}
     */
    public static function loadEventList($botId): ?array
    {
        $eventDatas = [
            ['id' => 1, 'bot_id' => $botId, 'event_title' => 'Campaign #1', 'configs' => 'Demo config data'],
            ['id' => 2, 'bot_id' => $botId, 'event_title' => 'Campaign #2', 'configs' => 'Demo config data'],
            ['id' => 3, 'bot_id' => $botId, 'event_title' => 'Campaign #3', 'configs' => 'Demo config data'],
            ['id' => 4, 'bot_id' => $botId, 'event_title' => 'Campaign #4', 'configs' => 'Demo config data'],
        ];

        $results = [];
        foreach ($eventDatas as $ed) {
            $results[] = new ModuleEvent(
                $botId,
                static::getModuleTag(),
                $ed['id'],
                $ed['event_title']
            );
        }

        return $results;
    }

    /**
     * Create a reply engine instance for this module.
     *
     * In this demo, the primary initialization parameter
     * is the module event ID.
     */
    public static function getBotModuleReplyEngine($botId, $initParams = ''): ?ReplyEngine
    {
        $demoReplyEngine = new DemoReplyEngine();
        $demoReplyEngine->demoModuleEventId = $initParams;

        return $demoReplyEngine;
    }
}