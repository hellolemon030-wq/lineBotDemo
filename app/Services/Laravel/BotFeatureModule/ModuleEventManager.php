<?php
namespace App\Services\Laravel\BotFeatureModule;

class ModuleEventManager{
    /** @var class-string<replyEngineModuleInterface>[] */
    protected array $moduleFactories = [];

    public function registerModuleReplyEngineFactory(string $factoryClassName)
    {
        if (!in_array($factoryClassName, $this->moduleFactories)) {
            $this->moduleFactories[] = $factoryClassName;
        }
    }

    /**
     * Get all events for a bot
     * @param string $botId
     * @return ModuleEvent[]
     */
    public function loadEventList(string $botId): array
    {
        $events = [];
        foreach ($this->moduleFactories as $factoryClass) {
            if (class_exists($factoryClass) && in_array(replyEngineModuleInterface::class, class_implements($factoryClass))) {
                $moduleEvents = $factoryClass::loadEventList($botId);
                if (is_array($moduleEvents)) {
                    $events = array_merge($events, $moduleEvents);
                }
            }
        }
        return $events;
    }
}