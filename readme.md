# LINE Bot Demo (Laravel + Docker)
Other languages:

- [日本語](readme.ja.md)

A Docker-based LINE Bot backend built with Laravel Sail.
Designed for fast setup, multi-bot support, and extensible reply handling.

**Core Features:**

1. Plug-and-play – clone the repo, configure `.env`, and run.
2. Multi-bot support – manage multiple LINE bots with database persistence.
3. Extensible AutoReply Engine – define custom reply engines with priority.
   - **Updated demo:** improved keyword/module matching mechanism; supports exact match, fuzzy match, and module-based replies.  
   - For details on the updated module system and event-based matching, see [Bot Feature Module Guide](app/Services/Laravel/BotFeatureModule/readme.md)
4. Asynchronous handling – external API calls (reply/push) can be processed asynchronously to protect core service stability.
5. CLI tools – manage LINE bots and user messages directly from the command line.
6. Automatic access token management – optimistic locking, auto-refresh when expired.
7. Message storage & workflow – all incoming messages are stored for history and processing.

---

## Requirements

- Docker & Docker Compose
- Composer

---

## Setup

### 1. Install dependencies

composer install

### 2. Environment configuration

cp .env.example .env

Edit `.env` and set the following values:

LINE_CHANNEL_ID=            # Default LINE Bot Channel ID  
LINE_CHANNEL_SECRET=       # Default LINE Bot Channel Secret  
LINE_MESSAGE_HANDLE_DIRECT_MODE=false

> When `LINE_MESSAGE_HANDLE_DIRECT_MODE` is set to true, the system will handle incoming LINE messages in **asynchronous mode**.  
> In this mode, API calls (reply/push) are processed via Laravel queue workers, which helps offload work from the main webhook and improve stability under load.  
> After completing the `.env` configuration, start the queue worker manually:./vendor/bin/sail artisan queue:work

### 3. Start Docker containers

./vendor/bin/sail up -d

### 4. Initialize application

./vendor/bin/sail artisan key:generate  
./vendor/bin/sail artisan migrate

### 5. System health check

./vendor/bin/sail artisan line:docker

This command checks database, queue, and bot system status.

---

## LINE Bot Configuration

### Register a LINE Bot (supports multiple bots)

./vendor/bin/sail artisan linebot:mg add [LINE_BOT_ID] [LINE_BOT_SECRET]

Example:

./vendor/bin/sail artisan linebot:mg add 1234567890 your_bot_secret

### Webhook configuration

The system supports both a default bot and multiple bots.

#### Default bot

If no bot ID is specified in the webhook URL, the system will use  
the default bot configured in `.env`:

https://your-domain/webhook

#### Multiple bots

After adding bots to the system, you may specify a bot key in the webhook URL:

https://your-domain/webhook/{BOT_KEY}

Example:

https://your-domain/webhook/1234567890

> The bot is ultimately identified by its Channel credentials.  
> The URL parameter is used to select the bot configuration.

---

## AutoReply Engine

1. `CoreEngine::webhook` receives incoming LINE requests.
2. The system verifies the bot and token using HMAC-SHA256.
3. Incoming messages are stored in the database.
4. AutoReply Engines are triggered in priority order:
   - **AI Test Engine** (demo, lightweight vector-based matching)
   - **ExactMatchEngine** (matches keywords exactly)
       - Supports:
         - **Common/Text Reply**: returns predefined text
         - **ModuleReplyEngine**: factory-based, triggers module events such as Weather, Lottery, etc.
   - **FuzzyMatchEngine** (matches keywords partially)
       - Supports:
         - **Common/Text Reply**
         - **ModuleReplyEngine**
   - **MediaReplyEngine** (handles non-text messages)
5. **Priority handling:** If any reply engine handles the message (adds a reply), the system stops checking further engines.
6. **Configuration:** You can adjust or add reply engines in `app/Providers/LineBotAppProvider.php`.

```php
    //app/Providers/LineBotAppProvider.php
    public function register(): void
    {
        // ......
        $this->app->singleton(ReplyEngine::class, function () {
            $aiReplyEngine = new FileBaseAi();
            $coreReplyEngine = new CoreReplyEngine([
                ['engine' => new TestReplyEngine(), 'priority' => 8],   // Adjust priority to change execution order
                ['engine' => $aiReplyEngine, 'priority' => 5],
            ]);
            $coreReplyEngine->addReplyEngine(new TestReplyEngine(), 4); // You can add new engines anytime
            return $coreReplyEngine;
        });
    }
```
> ⚠️ Note: This setup is for demonstration purposes only.  
> For production, develop and adjust reply engines according to your own business logic.
7. Replies are sent via LINE API, either synchronously or asynchronously, depending on the `.env` setting:

    LINE_MESSAGE_HANDLE_DIRECT_MODE=false   # synchronous mode (default)
    LINE_MESSAGE_HANDLE_DIRECT_MODE=true    # asynchronous mode (queue worker must be running)

    > When asynchronous mode is enabled, API calls (reply/push) are handled via Laravel queue workers.
    > Start the worker after configuration:

    ./vendor/bin/sail artisan queue:work
---

## Message Management (CLI)

### View incoming messages

./vendor/bin/sail artisan line:msg showList

### Reply to a user

./vendor/bin/sail artisan line:msg reply [LINE_BOT_ID] [USER_ID] [CONTENT]

Example:

./vendor/bin/sail artisan line:msg reply 1234567890 Uxxxxxxxx "Hello from CLI"

---

## Development

Stop containers:

./vendor/bin/sail down

Start specific services only (without redis):

./vendor/bin/sail up -d laravel.test mysql

---

## Module Relationship Diagram (ASCII)

```
[LINE Request]
      │
      ▼
[CoreEngine::webhook]
      │
      ├─ Verify Bot & Token (HMAC-SHA256)
      │
      ├─ Insert Message into DB
      │
      +-- Trigger AutoReply Engines             <-- Core: handle messages via priority-based engines
      |       │
      |       +-- AI Test Engine                (demo, lightweight vector-based matching)
      |       +-- ExactMatchEngine              (matches keywords exactly)
      |       |       ├─ Common/Text Reply      (replyContent)
      |       |       └─ ModuleReplyEngine     (factory-based, per module events: Weather, Lottery, etc.)
      |       +-- FuzzyMatchEngine              (matches keywords fuzzily)
      |       |       ├─ Common/Text Reply
      |       |       └─ ModuleReplyEngine
      |       +-- MediaReplyEngine               (handles non-text messages)
      |       +-- DescriptionReplyEngine         (demo description)
      |       +-- ......                         (custom engines: implement ReplyEngine per business logic)
      │
      └─ Send reply (sync / async) → LINE API

[CLI Tools]
      │
      ├─ linebot:mg     (Add/Modify Bots)
      ├─ line:msg       (View/Reply Messages)
      └─ line:replyRule      (Manage keyword/module reply rules: add/modify/delete)  <-- todo

[Jobs / Queue Workers]
      │
      └─ Handle async push/reply tasks to LINE API
```

---

## Notes

- Access tokens are stored in database and refreshed automatically
- Token refresh uses optimistic locking
- HTTP 401 triggers token refresh
- HTTP 403 indicates authorization failure and may require manual update

---