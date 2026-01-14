<?php
namespace App\Services\Laravel\ReplyEngines;

/**
 * NOTE:
 * Permission checks should NOT be handled here.
 * This manager is supposed to be called by upper-layer services
 * (e.g. admin panel, management APIs),
 * where bot ownership and authorization are already verified.
 */
class MatchRuleManager
{
    public function addNewMatchRule(MatchRule $matchRule)
    {
        $model = new MatchRuleModel();

        $model->bot_id = $matchRule->botId;
        $model->keywords = $matchRule->keyWords;
        $model->match_type = $matchRule->matchType;
        $model->reply_type = $matchRule->replyType;

        $model->reply_content = $matchRule->replyContent ?? null;
        $model->module_name = $matchRule->moduleName ?? null;
        $model->module_call_params = $matchRule->moduleCallParams ?? null;

        $model->save();

        return $model;
    }

    public function deleteMatchRuleById($matchRuleId)
    {
        return MatchRuleModel::where('id', $matchRuleId)->delete() > 0;
    }

    public function modifyMatchRule(MatchRule $matchRule)
    {
        if (!$matchRule->ruleId) {
            return false;
        }

        // Currently, only keyword and match type updates are supported
        return MatchRuleModel::where('id', $matchRule->ruleId)->update([
            'keywords'   => $matchRule->keyWords,
            'match_type'=> $matchRule->matchType,
        ]) > 0;
    }

    /**
     * Exact match lookup (used in user-facing message handling)
     */
    public function searchExactMatchRule($botId, $keyWord)
    {
        $record = MatchRuleModel::where('bot_id', $botId)
            ->where('match_type', MatchRule::MATCH_TYPE_EXACT)
            ->where('keywords', $keyWord)
            ->orderBy('id')
            ->first();

        if ($record) {
            return MatchRule::dbModel2MatchRule($record);
        }

        return null;
    }

    /**
     * Retrieve all fuzzy match rules for a specific bot
     */
    public function searchAllFuzzyMatchRuleByBotId($botId)
    {
        $records = MatchRuleModel::where('bot_id', $botId)
            ->where('match_type', MatchRule::MATCH_TYPE_FUZZY)
            ->orderBy('id')
            ->get();

        $result = [];
        foreach ($records as $record) {
            $result[] = MatchRule::dbModel2MatchRule($record);
        }

        return $result;
    }

    /**
     * List recent match rules for a bot (mainly for CLI / admin tools)
     *
     * @return MatchRuleModel[]
     */
    public function listMatchRule($botId, $count = 10)
    {
        return MatchRuleModel::where('bot_id', $botId)
            ->orderByDesc('id')
            ->limit($count)
            ->get();
    }

    public function queryMatchRuleByBotIdAndRuleId($botId, $id)
    {
        return MatchRuleModel::where('bot_id', $botId)
            ->where('id', $id)
            ->orderBy('id')
            ->first();
    }
}