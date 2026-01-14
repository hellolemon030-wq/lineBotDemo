<?php
namespace App\Services\Laravel\ReplyEngines;

use App\Services\LineBot\LineMessage;
use App\Services\LineBot\lineReplyMessage;
use App\Services\LineBot\ReplyEngine;

/**
 * FuzzyMatchEngine
 * 
 * Handles messages that partially match predefined keywords.
 * Only text messages are considered. Fuzzy match rules can trigger
 * common text replies or module-based replies (future extension).
 */
class FuzzyMatchEngine implements ReplyEngine
{
    /**
     * Handle incoming LineMessage and generate fuzzy reply.
     *
     * @param LineMessage $lineMessage
     * @param lineReplyMessage $lineReplyMessage
     * @return bool True if handled, false otherwise
     */
    public function handle(LineMessage $lineMessage, lineReplyMessage &$lineReplyMessage): bool
    {
        // Only process text messages
        if ($lineMessage->getMessageType() !== LineMessage::MESSAGE_TYPE_TEXT) {
            return false;
        }

        $rule = $this->matchRules($lineMessage->getBotKey(), $lineMessage->getMessageText());

        if (!$rule) {
            return false;
        }

        // Append the predefined reply content
        $lineReplyMessage->appendText($rule->replyContent);

        return true;
    }

    /**
     * Retrieve fuzzy match rule for the given bot and message content.
     *
     * Currently uses hard-coded test rules for development/demo purposes.
     * In production, this should query a database or cache.
     *
     * @param string $botId
     * @param string $messageText
     * @return MatchRule|null
     */
    private function matchRules(string $botId, string $messageText): ?MatchRule
    {
        $rules = [];

        // Fuzzy rule: keyword 'fuzzy'
        $rule1 = new MatchRule();
        $rule1->botId = $botId;
        $rule1->matchType = MatchRule::MATCH_TYPE_FUZZY;
        $rule1->replyType = MatchRule::REPLY_TYPE_COMMON;
        $rule1->keyWords = 'fuzzy';
        $rule1->replyContent = 'Fuzzy match test succeeded.';
        $rules[] = $rule1;

        /**
         * Simulated database-driven matching
         */
        $matchRuleManager = app()->get(MatchRuleManager::class);
        $fuzzyMatchRules = $matchRuleManager->searchAllFuzzyMatchRuleByBotId($botId);
        foreach($fuzzyMatchRules as $matchRule){
            $rules[] = $matchRule;
        }

        // Search for a fuzzy match
        foreach ($rules as $rule) {
            if ($rule->botId === $botId && mb_strpos($messageText, $rule->keyWords) !== false) {
                return $rule;
            }
        }

        return null;
    }
}