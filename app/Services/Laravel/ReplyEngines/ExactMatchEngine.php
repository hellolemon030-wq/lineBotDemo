<?php
namespace App\Services\Laravel\ReplyEngines;

use App\Services\Laravel\BotFeatureModule\ModuleBase;
use App\Services\Laravel\BotFeatureModule\WeatherModule;
use App\Services\LineBot\LineMessage;
use App\Services\LineBot\LineReplyMessage;
use App\Services\LineBot\ReplyEngine;

/**
 * ExactMatchEngine
 *
 * Handles messages that match predefined keywords exactly.
 * Supports both static text replies and module-based dynamic replies.
 */
class ExactMatchEngine implements ReplyEngine
{
    /**
     * Handle an incoming LINE message and generate a reply if matched.
     *
     * @param LineMessage       $lineMessage
     * @param LineReplyMessage  $lineReplyMessage
     * @return bool True if the message was handled, false otherwise
     *
     * @throws \Exception When the configured module class is invalid
     */
    public function handle(LineMessage $lineMessage, LineReplyMessage &$lineReplyMessage): bool
    {
        // Only text messages are supported by this engine
        if ($lineMessage->getMessageType() !== LineMessage::MESSAGE_TYPE_TEXT) {
            return false;
        }

        $rule = $this->matchRules(
            $lineMessage->getBotKey(),
            $lineMessage->getMessageText()
        );

        if (!$rule) {
            return false;
        }

        /**
         * Module-based reply handling
         */
        if ($rule->replyType === MatchRule::REPLY_TYPE_MODULE) {
            $moduleClass = $rule->moduleName;

            if (!class_exists($moduleClass)) {
                throw new \Exception("Module class '{$moduleClass}' does not exist.");
            }

            if (!is_subclass_of($moduleClass, ModuleBase::class)) {
                throw new \Exception("Module class '{$moduleClass}' must extend ModuleBase.");
            }

            $moduleReplyEngine = $moduleClass::getBotModuleReplyEngine(
                $lineMessage->getBotKey(),
                $rule->moduleCallParams
            );

            $moduleReplyEngine->handle($lineMessage, $lineReplyMessage);

            // Safety fallback: ensure at least one message is returned
            if (count($lineReplyMessage->getMessages()) === 0) {
                $lineReplyMessage->appendText(
                    'Request was handled by module: ' . $moduleClass
                );
            }

            return true;
        }

        /**
         * Common static text reply
         */
        if ($rule->replyType === MatchRule::REPLY_TYPE_COMMON) {
            $lineReplyMessage->appendText($rule->replyContent);
            return true;
        }

        return false;
    }

    /**
     * Match message text against exact-match rules.
     *
     * NOTE:
     * - The upper section uses hard-coded rules for demo purposes.
     * - The lower section delegates to MatchRuleManager to simulate
     *   database-driven rule matching.
     *
     * @param string $botId
     * @param string $messageText
     * @return MatchRule|null
     */
    private function matchRules(string $botId, string $messageText): ?MatchRule
    {
        $rules = [];

        /**
         * Demo: module-based weather rules
         */
        $tokyoRule = new MatchRule();
        $tokyoRule->botId = $botId;
        $tokyoRule->replyType = MatchRule::REPLY_TYPE_MODULE;
        $tokyoRule->moduleName = WeatherModule::class;
        $tokyoRule->moduleCallParams = 'tokyo';
        $tokyoRule->keyWords = '東京天気';
        $rules[] = $tokyoRule;

        $osakaRule = new MatchRule();
        $osakaRule->botId = $botId;
        $osakaRule->replyType = MatchRule::REPLY_TYPE_MODULE;
        $osakaRule->moduleName = WeatherModule::class;
        $osakaRule->moduleCallParams = 'osaka';
        $osakaRule->keyWords = '大阪天気';
        $rules[] = $osakaRule;

        /**
         * Help / usage documentation (static reply)
         */
        $helpDoc = <<<TEXT
This is a demo LINE Bot.

Auto-reply engines are executed in priority order.
Once a message is handled by one engine,
subsequent engines will NOT be executed.

1. AI Test Engine (experimental)
- A lightweight AI-based matching engine for development testing only.
- Uses simple vector-style matching and is not intended to be accurate.
- Triggered when the message starts with "ai:" or "ai：" (e.g. "ai: tuition").

2. Exact Match Engine
- The message must exactly match a predefined keyword.
- Supported keywords:
  • 東京天気
  • 大阪天気
- You can also register new rules via the CLI command:
  line:replyRule add ...

3. Fuzzy Match Engine
- Matches messages containing predefined keywords.
- Example keyword:
  • fuzzy (e.g. "abc fuzzy xyz")

4. Media Reply Engine
- Handles non-text messages such as images or videos.

5. Fallback
- If no rule matches, the system will acknowledge receipt of the message.
TEXT;

        $helpRule = new MatchRule();
        $helpRule->botId = $botId;
        $helpRule->replyType = MatchRule::REPLY_TYPE_COMMON;
        $helpRule->replyContent = $helpDoc;
        $helpRule->keyWords = '使用方法';
        $rules[] = $helpRule;

        /**
         * Exact match against demo rules
         */
        foreach ($rules as $rule) {
            if ($rule->botId === $botId && $rule->keyWords === $messageText) {
                return $rule;
            }
        }

        /**
         * Simulated database-driven matching
         */
        $matchRuleManager = app()->get(MatchRuleManager::class);
        return $matchRuleManager->searchExactMatchRule($botId, $messageText);
    }
}