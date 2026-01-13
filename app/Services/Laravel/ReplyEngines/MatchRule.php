<?php
namespace App\Services\Laravel\ReplyEngines;

/**
 * Class MatchRule
 *
 * Represents a single keyword matching rule for the LINE Bot auto-reply system.
 * Can be either an exact match, fuzzy match, or linked to a module/event for dynamic replies.
 */
class MatchRule
{
    // -----------------------------
    // Reply Types
    // -----------------------------
    const REPLY_TYPE_COMMON = 'common';   // Static text reply
    const REPLY_TYPE_MODULE = 'module';   // Reply handled by a module
    const REPLY_TYPE_EVENT  = 'event';    // Reply handled by a specific event

    // -----------------------------
    // Match Types
    // -----------------------------
    const MATCH_TYPE_EXACT = 'exact';
    const MATCH_TYPE_FUZZY = 'fuzzy';

    // -----------------------------
    // Properties
    // -----------------------------
    public int $ruleId;
    public string $botId;
    public string $keyWords;
    public string $replyType;          // common / module / event
    public string $replyContent;       // Used for common replies
    public string $eventId = '';       // Associated event ID, if replyType=event

    // Module-related fields
    public string $moduleName = '';        // Module class name if replyType=module
    public string $moduleCallParams = '';  // Parameters for initializing the module reply engine

    public string $matchType;          // exact / fuzzy

    // -----------------------------
    // Static Methods
    // -----------------------------

    /**
     * Create a test instance (mock) for development/demo purposes.
     * In production, this method would query the database to retrieve
     * a real MatchRule instance by botId and keyWords.
     *
     * @param string $botId
     * @param string $keyWords
     * @return MatchRule
     */
    public static function getInstanceByDb(string $botId, string $keyWords): self
    {
        $instance = new self();
        $instance->botId = $botId;
        $instance->keyWords = $keyWords;
        $instance->replyType = self::REPLY_TYPE_COMMON;
        $instance->replyContent = 'Test reply content for matched keyword.';
        $instance->matchType = self::MATCH_TYPE_EXACT;

        // Module/event fields remain empty for common replies
        $instance->moduleName = '';
        $instance->moduleCallParams = '';
        $instance->eventId = '';

        return $instance;
    }
}