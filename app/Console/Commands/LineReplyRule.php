<?php

namespace App\Console\Commands;

use App\Services\Laravel\BotFeatureModule\ModuleManager;
use App\Services\Laravel\ReplyEngines\MatchRule;
use App\Services\Laravel\ReplyEngines\MatchRuleManager;
use Illuminate\Console\Command;

class LineReplyRule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'line:replyRule {action} {p1?} {p2?} {p3?} {p4?} {p5?} {p6?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reply rule management command';

    /**
     * Execute the console command.
     *
     * Available actions:
     *
     * line:replyRule showModuleEventList [botId]
     *   - Show all available module events that can be bound to reply rules for the specified bot.
     *
     * line:replyRule showReplyRule [botId]
     *   - Show all existing reply rules under the specified bot.
     *
     * line:replyRule addReplyRule [botId] [matchMode(exact|fuzzy)] [keyword] common [replyText]
     *   - Create a reply rule that returns a fixed text response.
     *
     * line:replyRule addReplyRule [botId] [matchMode(exact|fuzzy)] [keyword] module [moduleTag] [moduleCallParam]
     *   - Create a reply rule handled by a specific feature module.
     *
     * line:replyRule modifyReplyRule [botId] [ruleId] [matchMode] [keyword]
     *   - Modify basic rule properties (execution content is not modified).
     *     For content changes, it is recommended to delete and recreate the rule.
     *
     * line:replyRule delReplyRule [botId] [ruleId]
     *   - Delete a reply rule by rule ID.
     */
    public function handle()
    {
        // TODO:
        // Validate whether the specified bot exists before processing commands.
        //
        // $botId = $this->argument('p1');
        // $botManager = app()->get(StoreBotManager::class);
        // $botInstance = $botManager->getRuntimeBot($botId);
        // if (empty($botInstance)) {
        //     $this->error('Bot does not exist.');
        // }

        $action = $this->argument('action');
        $mgr = app()->get(MatchRuleManager::class);
        $moduleManager = app()->get(ModuleManager::class);

        switch ($action) {

            case 'showModuleEventList':
                $botId = $this->argument('p1');

                // Load all module events that can be bound as reply handlers
                $events = $moduleManager->loadEventList($botId);

                $rows = [];
                foreach ($events as $event) {
                    $rows[] = [
                        'ModuleTag'   => $event->moduleTag,
                        'EventId'     => $event->moduleEventUniqueId,
                        'Description' => $event->humanField,
                        'addCommand'  => "line:replyRule add {$botId} exact [keyword] module '{$event->moduleTag}' {$event->moduleEventUniqueId}"
                    ];
                }

                $this->table(
                    ['ModuleTag', 'EventId', 'Description', 'AddCommand'],
                    $rows
                );
                break;

            case 'show':
            case 'showReplyRule':
                $botId = $this->argument('p1');

                // Fetch existing reply rules for the specified bot
                $rules = $mgr->listMatchRule($botId, 50);

                $rows = [];
                foreach ($rules as $rule) {
                    $rows[] = [
                        'ruleId'          => $rule->id,
                        'matchType'       => $rule->match_type,
                        'keyword'         => $rule->keywords,
                        'replyType'       => $rule->reply_type,
                        'replyText'       => $rule->reply_content,
                        'module'          => $rule->module_name,
                        'moduleCallParam' => $rule->module_call_params,
                    ];
                }

                $this->table(
                    ['RuleId', 'MatchType', 'Keyword', 'ReplyType', 'ReplyText', 'Module', 'ModuleCallParam'],
                    $rows
                );
                break;

            case 'add':
            case 'addReplyRule':
                $botId     = $this->argument('p1');
                $matchMode = $this->argument('p2');
                $keyword   = $this->argument('p3');
                $type      = $this->argument('p4');

                // Validate match mode
                if (
                    $matchMode !== MatchRule::MATCH_TYPE_EXACT &&
                    $matchMode !== MatchRule::MATCH_TYPE_FUZZY
                ) {
                    $this->error(
                        'matchMode must be "' .
                        MatchRule::MATCH_TYPE_EXACT .
                        '" or "' .
                        MatchRule::MATCH_TYPE_FUZZY .
                        '"'
                    );
                }

                $rule = new MatchRule();
                $rule->botId    = $botId;
                $rule->keyWords = $keyword;
                $rule->matchType = $matchMode;
                $rule->replyType = $type;

                // Demo limitation: fuzzy matching does not support module-based replies
                if (
                    $matchMode === MatchRule::MATCH_TYPE_FUZZY &&
                    $type === MatchRule::REPLY_TYPE_MODULE
                ) {
                    $this->error(
                        'For demo purposes, fuzzy matching only supports text replies. ' .
                        'Module-based replies require exact matching.'
                    );
                }

                if ($type === MatchRule::REPLY_TYPE_COMMON) {
                    $rule->replyContent = $this->argument('p5');
                } elseif ($type === MatchRule::REPLY_TYPE_MODULE) {
                    $rule->moduleName = $this->argument('p5');
                    $rule->moduleCallParams = $this->argument('p6') ?? null;
                } else {
                    $this->error('replyType must be "common" or "module"');
                }

                $mgr->addNewMatchRule($rule);
                $this->info('Reply rule added successfully.');
                break;

            case 'del':
            case 'delReplyRule':
                // Validate ownership by botId before deletion
                $botId  = (int)$this->argument('p1');
                $ruleId = (int)$this->argument('p2');

                $record = $mgr->queryMatchRuleByBotIdAndRuleId($botId, $ruleId);
                if (empty($record)) {
                    $this->info('No matching rule found for the given botId and ruleId.');
                    break;
                }

                $result = $mgr->deleteMatchRuleById($ruleId);
                if ($result > 0) {
                    $this->info('Reply rule deleted successfully.');
                } else {
                    $this->info('Failed to delete reply rule.');
                }
                break;

            default:
                $this->error('Unknown action.');
        }
    }
}