# LINE Bot Auto-Reply Module Architecture

## 1. Overview

The auto-reply system for the LINE Bot is modular, based on functional modules and module events.  
Each module may create multiple events, which can be linked to match rules for auto-reply.  

Main components:

- ModuleEventManager: Central registry for all module events (acts as a factory manager)  
- MatchRuleManager: Manages matching rules that connect keywords to replies or module events  
- Modules: Each functional module (Weather, Lottery, Questionnaire, etc.) implements replyEngineModuleInterface to provide event lists and generate reply engines  

---

## 2. ModuleEventManager

Purpose:  
Manages all module factories and collects events for a given bot. Supports factory-based events, no direct DB operations are needed for non-persistent modules (e.g., weather module)

Core Methods:

- registerModuleReplyEngineFactory(factoryClassName)  
- loadEventList(botId): ModuleEvent[]

ModuleEvent Structure:

Field | Description
--- | ---
botId | The bot/public account ID
moduleTag | Module identifier (e.g., WeatherModule, LotteryModule)
moduleEventUniqueId | Unique string or ID for this event
humanField | Human-readable description (shown in front-end)

Factory Execution Flow:

1. loadEventList(botId) iterates all registered factories  
2. For each factory: calls factoryClass::loadEventList(botId) to get module-specific events  
3. Returns a flat list of ModuleEvent instances for selection  

Example: WeatherModule returns two events for bot_001: "Tokyo Weather", "Osaka Weather"

---

## 3. MatchRuleManager

Purpose:  
Stores and manages keyword matching rules for auto-reply

MatchRule Structure:

Field | Description
--- | ---
botId | The bot/public account ID
matchType | 'exact' or 'fuzzy'
replyType | 'common' or 'module'
replyContent | For common replies: text to respond
moduleTag | For module replies: the module identifier
moduleCallParams | For module replies: parameters used to initialize module's reply engine

Usage Flow:

1. Admin selects matching type (exact/fuzzy) and keyword  
2. Admin selects module event from ModuleEventManager->loadEventList(botId) if replyType=module  
3. System generates a MatchRule with either:  
   - replyType=common: replyContent is text  
   - replyType=module: moduleTag + moduleCallParams link to module event  
4. When a message arrives, the auto-reply engine executes:  
   - Exact text match → stop at first match  
   - Fuzzy text match → stop at first match  
   - Non-text message → handled by MediaReplyEngine  
   - Fallback → default message  

---

## 4. Modules

### 4.1 WeatherModule (Example)

- Module tag: WeatherModule  
- Purpose: Provide weather info for different areas  
- Factory Output:  
  - ModuleEvent(botId, 'WeatherModule', 'tokyo', 'Tokyo Weather')  
  - ModuleEvent(botId, 'WeatherModule', 'osaka', 'Osaka Weather')  

- Reply Engine: Generates reply containing weather info for the selected area

### 4.2 LotteryModule (Example)

- Module tag: LotteryModule  
- Purpose: Handle lottery events/activities  
- Factory Output:  
  - ModuleEvent(botId, 'LotteryModule', lotteryEventId, 'New Year Lottery')  

- lotteryEventId is the ID from the module's business table (auto-increment)  
- Front-end displays humanField for selection  
- Multiple lottery events per bot are supported

---

## 5. Auto-Reply Execution Flow (Demo Bot)

0. AI test engine (experimental)
   - A lightweight AI-based text matching engine for development/testing
   - If a text message starts with "ai:" or "ai：" (e.g., "ai: 学费"), this engine will handle the message
   - Uses simple vector-based matching, NOT intended for production accuracy
   - Once matched, subsequent engines will NOT be executed

1. Exact match engine  
   - Keywords must match exactly  
   - Example keywords: 東京天気, 大阪天気  

2. Fuzzy match engine  
   - Message contains the keyword  
   - Example keywords: fuzzy, 使用方法  

3. Non-text message handling  
   - Handled by media reply engine (images, video, etc.)  

4. Fallback  
   - If none of the above match, a default message is returned

---

## 6. Notes

- Modules can be static and do not need instantiation  
- ModuleEventManager + factory approach supports:  
  - Modules that generate dynamic events (lottery, questionnaires)  
  - Modules that do not generate events (weather, static config)  
- Front-end displays humanField to select the appropriate module event for match rule creation  