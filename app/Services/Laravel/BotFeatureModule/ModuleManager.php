<?php
namespace App\Services\Laravel\BotFeatureModule;

class ModuleManager
{
    /**
     * Registered module class list.
     *
     * @var class-string<ModuleBase>[]
     */
    protected array $_modules = [];

    /**
     * Register a feature module into the system.
     *
     * The module must:
     * - Exist as a class
     * - Extend ModuleBase
     *
     * @param string $moduleTag Fully-qualified module class name
     */
    public function registerModule(string $moduleTag): void
    {
        if (!in_array($moduleTag, $this->_modules, true)) {

            // Validate that the class exists and extends ModuleBase
            if (class_exists($moduleTag) && is_subclass_of($moduleTag, ModuleBase::class)) {
                $this->_modules[] = $moduleTag;
            } else {
                throw new \InvalidArgumentException(
                    "$moduleTag must exist and extend ModuleBase"
                );
            }
        }
    }

    /**
     * Load all available module events for a specific bot.
     *
     * This method is mainly used by the reply matching engine
     * to discover which module-level events can be bound to
     * keyword-based reply rules.
     *
     * @param string $botId
     * @return ModuleEvent[]
     */
    public function loadEventList(string $botId): array
    {
        $events = [];

        foreach ($this->_modules as $module) {
            if ($module::_isAllowModuleReply()) {

                $moduleEvents = $module::loadEventList($botId);

                if (is_array($moduleEvents)) {
                    $events = array_merge($events, $moduleEvents);
                }
            }
        }

        return $events;
    }
}