# LINE Bot Reply Engine Architecture & Usage (Demo)

## 1. Overview

This system implements a rule-based LINE Bot auto-reply architecture with optional module-driven replies.

Key characteristics:
- Priority-based reply engines
- Database-driven match rules (exact / fuzzy)
- Extensible feature modules (DemoModule, WeatherModule, etc.)
- CLI tools for rule and module management

---

## 2. High-Level Request Flow

```php
        $this->app->singleton(ReplyEngine::class,function(){
            //$aiReplyEngine = new FileBaseAi();
            // $coreReplyEngine = new CoreReplyEngine([
            //     ['engine' => new TestReplyEngine(), 'priority' => 8],
            //     ['engine' => $aiReplyEngine, 'priority' => 5],
            // ]);
            // $coreReplyEngine->addReplyEngine(new TestReplyEngine,4);
            $coreReplyEngine = new CoreReplyEngine([
                ['engine' => new ExactMatchEngine(), 'priority' => 99],
                ['engine' => new FuzzyMatchEngine(), 'priority' => 98],
                ['engine' => new MediaReplyEngine(), 'priority' => 97],
                ['engine' => new DescriptionReplyEngine(), 'priority' => 96],
            ]);
            /**
             * AI-based reply engine
             * Used for special demo keywords or fallback scenarios.
             */
            $coreReplyEngine->addReplyEngine(new EasyAiReplyEngine,100);    
            return $coreReplyEngine;
        });
```
```
[LINE Webhook Request]
        │
        ▼
[CoreEngine::webhook]
        │
        ├─ Verify Bot Signature (HMAC-SHA256)
        │
        ├─ Persist Incoming Message
        │
        └─ Trigger Reply Engines (priority order)
                │
                ├─ EasyAiReplyEngine   (prefix-based, e.g. "ai:")
                ├─ ExactMatchEngine    (DB-driven exact match)
                │       ├─ Common Text Reply / Module Reply (Demo / Weather / etc.)
                ├─ FuzzyMatchEngine    (DB-driven fuzzy match)
                ├─ MediaReplyEngine    (non-text messages)
                └─ Fallback Reply
        │
        └─ Send Reply → LINE Messaging API (sync / async)
```

---

## 3. Match Rule Based Reply Logic

3.1 Core Flow (Simplified Pseudo Code)
```php
// Initialize managers
$matchRuleManager = app(MatchRuleManager::class);

$lineMessage = new LineMessage();
$lineReplyMessage = new LineReplyMessage();

// 1. AI prefix handling (highest priority)
if (preg_match('/^\s*ai[:：]/i', $lineMessage->getMessageText())) {
    $lineReplyMessage->appendText('This is an AI-generated reply');
    return;
}

// 2. Exact match search
$rule = $matchRuleManager->searchExactMatchRule(
    $lineMessage->getBotKey(),
    $lineMessage->getMessageText()
);

if ($rule) {
    if ($rule->reply_type === MatchRule::REPLY_TYPE_COMMON) {
        $lineReplyMessage->appendText($rule->reply_content);
        return;
    }

    if ($rule->reply_type === MatchRule::REPLY_TYPE_MODULE) {
        // Delegate to module engine
         $replyEngine = ModuleBase::getModuleMainByTag($MatchRule->moduleName)::getBotModuleReplyEngine(
            $lineMessage->getBotKey(),
            $MatchRule->moduleCallParams
         );
         $replyEngine->handle($lineMessage,$lineReplyMessage);  //handle by module's replyEngine;
         return;
    }
}

// 3. Fuzzy match search
$fuzzyRules = $matchRuleManager->searchAllFuzzyMatchRuleByBotId(
    $lineMessage->getBotKey(),
    $lineMessage->getMessageText()
);

foreach ($fuzzyRules as $rule) {
    if (mb_strpos($lineMessage->getMessageText(), $rule->keywords) !== false) {
        $lineReplyMessage->appendText($rule->reply_content);
        return;
    }
}

// 4. Non-text or fallback handling
if($lineMessage->getMessageType() != LineMessage::MESSAGE_TYPE_TEXT){
   $lineReplyMessage->appendText(
      'This message is handled by MediaReplyEngine.'
   );
}
```
---

## 4. MatchRule Table Design

All reply behaviors are controlled by the match_rules table.

Example record:

```
id: 7
bot_id: 1234567890
keywords: demotest
match_type: exact
reply_type: module
reply_content: NULL
module_name: App\\Services\\Laravel\\BotFeatureModule\\DemoModule\\DemoModule
module_call_params: 2
created_at: 2026-01-14 23:08:11
updated_at: 2026-01-14 23:08:11
```

Field Explanation
- match_type: exact | fuzzy
- reply_type: common → plain text reply / module → delegate to a feature module
- reply_content: used only when reply_type = common
- module_name: fully-qualified class name of the module
- module_call_params: event identifier passed into the module

---

## 5. Feature Modules (Demo-Based Design)

There is no generic BotFeatureModule base directory.
New modules are developed by referencing DemoModule and WeatherModule.

5.1 Module Registration
```php
$moduleManager = new ModuleManager();

$moduleManager->registerModule(DemoModule::class);
$moduleManager->registerModule(WeatherModule::class);
```

Registered modules declare which events they support for a given bot.

---

## 6. Module Event Discovery
```php
$botId = '1234567890';
$eventList = $moduleManager->loadEventList($botId);

foreach ($eventList as $event) {
    $event->moduleTag;            // Module identifier
    $event->moduleEventUniqueId;  // Event ID
}

//line:replyRule add [botId] [exact|fuzzy] [keyword] module $event->moduleTag $event->moduleEventUniqueId
```

These events are used to generate module-type match rules.

---

## 7. CLI Tools

7.1 Manage Reply Rules

- Add rule:
```
line:replyRule add [botId] [exact|fuzzy] [keyword] common [replyText]
line:replyRule add [botId] [exact|fuzzy] [keyword] module [ModuleClass] [eventId]
```
- List rules:
```
line:replyRule show [botId]
```
- Delete rule:
```
line:replyRule del [botId] [ruleId]
```

---

## 8. Module Event Helper Command

line:replyRule showModuleEvents [botId]

Example output:

```
+-------------------------------------------------------------+---------+---------------+----------------------------------------------------------------------------------------------------------------------+
| ModuleTag                                                   | EventId | Description   | AddCommand                                                                                                           |
+-------------------------------------------------------------+---------+---------------+----------------------------------------------------------------------------------------------------------------------+
| App\Services\Laravel\BotFeatureModule\DemoModule\DemoModule | 1       | Campaign #1   | line:replyRule add 1234567890 exact [keyword] module 'App\Services\Laravel\BotFeatureModule\DemoModule\DemoModule' 1 |
| App\Services\Laravel\BotFeatureModule\DemoModule\DemoModule | 2       | Campaign #2   | line:replyRule add 1234567890 exact [keyword] module 'App\Services\Laravel\BotFeatureModule\DemoModule\DemoModule' 2 |
| App\Services\Laravel\BotFeatureModule\DemoModule\DemoModule | 3       | Campaign #3   | line:replyRule add 1234567890 exact [keyword] module 'App\Services\Laravel\BotFeatureModule\DemoModule\DemoModule' 3 |
| App\Services\Laravel\BotFeatureModule\DemoModule\DemoModule | 4       | Campaign #4   | line:replyRule add 1234567890 exact [keyword] module 'App\Services\Laravel\BotFeatureModule\DemoModule\DemoModule' 4 |
| App\Services\Laravel\BotFeatureModule\WeatherModule         | tokyo   | Tokyo Weather | line:replyRule add 1234567890 exact [keyword] module 'App\Services\Laravel\BotFeatureModule\WeatherModule' tokyo     |
| App\Services\Laravel\BotFeatureModule\WeatherModule         | osaka   | Osaka Weather | line:replyRule add 1234567890 exact [keyword] module 'App\Services\Laravel\BotFeatureModule\WeatherModule' osaka     |
+-------------------------------------------------------------+---------+---------------+----------------------------------------------------------------------------------------------------------------------+
```


The AddCommand column is provided for easy copy & paste by administrators.

---

## 9. Summary
	•	Reply behavior is fully DB-driven
	•	Exact match → highest priority
	•	Module reply enables business feature isolation
	•	DemoModule serves as the reference implementation
	•	System is designed for extensibility and maintainability

This document represents the final and current design.