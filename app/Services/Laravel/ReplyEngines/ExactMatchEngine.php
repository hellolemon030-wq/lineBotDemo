<?php
namespace App\Services\Laravel\ReplyEngines;

use App\Services\Laravel\BotFeatureModule\ModuleBase;
use App\Services\Laravel\BotFeatureModule\WeatherModule;
use App\Services\LineBot\LineMessage;
use App\Services\LineBot\lineReplyMessage;
use App\Services\LineBot\ReplyEngine;

/**
 * ExactMatchEngine
 * 
 * Handles messages that match predefined keywords exactly. 
 * Supports both common text replies and module-based dynamic replies.
 */
class ExactMatchEngine implements ReplyEngine
{
    /**
     * Handle incoming LineMessage and generate appropriate reply.
     *
     * @param LineMessage $lineMessage
     * @param lineReplyMessage $lineReplyMessage
     * @return bool true if handled, false otherwise
     * @throws \Exception if module class is invalid
     */
    public function handle(LineMessage $lineMessage, lineReplyMessage &$lineReplyMessage): bool
    {
        // Only handle text messages
        if ($lineMessage->getMessageType() !== LineMessage::MESSAGE_TYPE_TEXT) {
            return false;
        }

        $rule = $this->matchRules($lineMessage->getBotKey(), $lineMessage->getMessageText());
        if (!$rule) {
            return false;
        }

        // Handle module-based replies
        if ($rule->replyType === MatchRule::REPLY_TYPE_MODULE) {
            $moduleClass = $rule->moduleName;

            if (!class_exists($moduleClass)) {
                throw new \Exception("Module class '{$moduleClass}' does not exist.");
            }

            if (!is_subclass_of($moduleClass, ModuleBase::class)) {
                throw new \Exception("Module class '{$moduleClass}' must extend ModuleBase.");
            }

            // Initialize module reply engine
            $moduleInstance = $moduleClass::getBotModuleReplyEngine(
                $lineMessage->getBotKey(),
                $rule->moduleCallParams
            );

            $moduleInstance->handle($lineMessage, $lineReplyMessage);

            // If no messages were appended by the module, add a default info message
            if (!count($lineReplyMessage->getMessages())) {
                $lineReplyMessage->appendText("Handled by module: " . $moduleClass);
            }

            return true;
        }

        // Handle common text replies
        if ($rule->replyType === MatchRule::REPLY_TYPE_COMMON) {
            $lineReplyMessage->appendText($rule->replyContent);
            return true;
        }

        return false;
    }

    /**
     * Match message text against predefined rules (exact match).
     * 
     * This is a placeholder for database-driven rules. 
     * Currently, rules are hard-coded for development/testing purposes.
     *
     * @param string $botId
     * @param string $messageText
     * @return MatchRule|null
     */
    private function matchRules(string $botId, string $messageText): ?MatchRule
    {
        $rules = [];

        // Module-based rules
        $tokyoRule = new MatchRule();
        $tokyoRule->botId = $botId;
        $tokyoRule->replyType = MatchRule::REPLY_TYPE_MODULE;
        $tokyoRule->moduleName = WeatherModule::class;
        $tokyoRule->moduleCallParams = json_encode(['area' => 'tokyo']);
        $tokyoRule->keyWords = '東京天気';
        $rules[] = $tokyoRule;

        $osakaRule = new MatchRule();
        $osakaRule->botId = $botId;
        $osakaRule->replyType = MatchRule::REPLY_TYPE_MODULE;
        $osakaRule->moduleName = WeatherModule::class;
        $osakaRule->moduleCallParams = json_encode(['area' => 'osaka']);
        $osakaRule->keyWords = '大阪天気';
        $rules[] = $osakaRule;

        // Common text rule
        $commonRule = new MatchRule();
        $commonRule->botId = $botId;
        $commonRule->replyType = MatchRule::REPLY_TYPE_COMMON;
        $commonRule->replyContent = 'Exact keyword match succeeded.';
        $commonRule->keyWords = 'key word test';
        $rules[] = $commonRule;

        // Search for exact match
        foreach ($rules as $rule) {
            if ($rule->botId === $botId && $rule->keyWords === $messageText) {
                return $rule;
            }
        }

        return null;
    }
}