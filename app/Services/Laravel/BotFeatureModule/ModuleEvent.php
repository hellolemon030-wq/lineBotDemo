<?php
namespace App\Services\Laravel\BotFeatureModule;

/**
 * Represents a single module event
 */
class ModuleEvent {
    public string $botId;
    public string $moduleTag;
    public string $moduleEventUniqueId;
    public string $humanField;

    public function __construct(string $botId, string $moduleTag, string $moduleEventUniqueId, string $humanField)
    {
        $this->botId = $botId;
        $this->moduleTag = $moduleTag;
        $this->moduleEventUniqueId = $moduleEventUniqueId;
        $this->humanField = $humanField;
    }
}