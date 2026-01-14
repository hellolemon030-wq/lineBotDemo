# LINE Bot Demo (Laravel + Docker)

Other languages:
- [日本語](readme.ja.md)

A Docker-based LINE Bot backend built with Laravel Sail.  
Designed for fast setup, multi-bot support, and a scalable auto-reply architecture.

---

## Core Features

1. **Plug-and-play setup**  
   Clone the repository, configure `.env`, and start the service using Docker.

2. **Multi-bot support**  
   Manage multiple LINE bots with persistent storage and isolated configurations.

3. **Extensible auto-reply engine (rule-based)**  
   A priority-based reply engine system that supports:
   - Exact keyword matching  
   - Fuzzy keyword matching  
   - Module-based replies (event-driven)

   The reply logic is fully database-driven and can be extended by adding new reply engines or feature modules.

   👉 For detailed architecture and usage, see:  
   [LINE Bot Reply Engine Architecture & Usage](app/Services/Laravel/BotFeatureModule/readme.md)

4. **Module-based feature design**  
   Business logic is encapsulated into independent feature modules (e.g. `DemoModule`, `WeatherModule`).  
   Each module can expose multiple events that can be bound to keyword rules.

5. **Asynchronous processing**  
   External API calls (reply / push) can be processed asynchronously via queues to ensure system stability.

6. **CLI tools for management**  
   Command-line utilities to:
   - Manage LINE bots  
   - Configure reply rules  
   - Inspect incoming messages  

7. **Automatic access token management**  
   LINE access tokens are refreshed automatically with optimistic locking to avoid race conditions.

8. **Message persistence & workflow support**  
   All incoming messages are stored for auditing, debugging, and further processing.

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
👉 For detailed architecture and usage, see:  
   [LINE Bot Reply Engine Architecture & Usage](app/Services/Laravel/BotFeatureModule/readme.md)

Replies are sent via LINE API, either synchronously or asynchronously, depending on the `.env` setting:

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
[LINE Webhook Request]
        │
        ▼
[CoreEngine::webhook]
        │
        ├─ Verify Bot & Token (HMAC-SHA256)
        │
        ├─ Insert Message into DB
        │
        ├─ Trigger Auto-Reply Engines
        │       (Core: handle messages via priority-based engines)
        │
        │       ├─ AI Test Engine
        │       │     (demo, lightweight vector-based matching)
        │       │
        │       ├─ ExactMatchEngine
        │       │     (matches keywords exactly)
        │       │     ├─ Text Reply
        │       │     │     (replyContent)
        │       │     └─ Module Reply Engine
        │       │           (factory-based, per module events:
        │       │            Weather, Lottery, etc.)
        │       │
        │       ├─ FuzzyMatchEngine
        │       │     (matches keywords fuzzily)
        │       │     └─ Text Reply
        │       │
        │       ├─ MediaReplyEngine
        │       │     (handles non-text messages)
        │       │
        │       ├─ DescriptionReplyEngine
        │       │     (demo description)
        │       │
        │       └─ Custom Reply Engines
        │             (implement ReplyEngine per business logic)
        │
        └─ Send Reply (sync / async) → LINE Messaging API


[CLI Tools]
        │
        ├─ linebot:mg
        │     (add / modify bots)
        │
        ├─ line:msg
        │     (view / reply messages)
        │
        └─ line:replyRule
              (manage keyword / module reply rules:
               add / modify / delete)


[Jobs / Queue Workers]
        │
        └─ Handle async push / reply tasks
              → LINE Messaging API
```

---

## Notes

- Access tokens are stored in database and refreshed automatically
- Token refresh uses optimistic locking
- HTTP 401 triggers token refresh
- HTTP 403 indicates authorization failure and may require manual update

---


