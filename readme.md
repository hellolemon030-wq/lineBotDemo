# LINE Bot Demo (Laravel + Docker)

A Docker-based LINE Bot backend built with Laravel Sail.
Clone and run with minimal configuration.
Supports multiple LINE bots, automatic access token refresh, and CLI-based message management.

---

## Requirements

- Docker & Docker Compose
- Composer

---

## Setup

### 1. Install dependencies

composer install

---

### 2. Environment configuration

cp .env.example .env

Edit `.env` and set the following values:

LINE_CHANNEL_ID=            # Default LINE Bot Channel ID  
LINE_CHANNEL_SECRET=       # Default LINE Bot Channel Secret  

LINE_MESSAGE_HANDLE_DIRECT_MODE=false

When LINE_MESSAGE_HANDLE_DIRECT_MODE is set to true,
you must manually start the queue worker:

./vendor/bin/sail artisan queue:work

---

### 3. Start Docker containers

./vendor/bin/sail up -d

---

### 4. Initialize application

./vendor/bin/sail artisan key:generate  
./vendor/bin/sail artisan migrate

---

### 5. System health check

./vendor/bin/sail artisan line:docker

This command checks database, queue, and bot system status.

---

## LINE Bot Configuration

### Register a LINE Bot (supports multiple bots)

./vendor/bin/sail artisan linebot:mg add [LINE_BOT_ID] [LINE_BOT_SECRET]

Example:

./vendor/bin/sail artisan linebot:mg add 1234567890 your_bot_secret

---

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

The bot is ultimately identified by its Channel credentials.
The URL parameter is used to select the bot configuration.

## Message Management (CLI)

### View incoming messages

./vendor/bin/sail artisan line:msg showList

### Reply to a user

./vendor/bin/sail artisan line:msg reply [LINE_BOT_ID] [USER_ID] [CONTENT]

Example:

./vendor/bin/sail artisan line:msg reply 1234567890 Uxxxxxxxx "Hello from CLI"

---

## Notes

- Access tokens are stored in database and refreshed automatically
- Token refresh uses optimistic locking
- HTTP 401 triggers token refresh
- HTTP 403 indicates authorization failure and may require manual update

---

## Development

Stop containers:

./vendor/bin/sail down

Start specific services only (without redis):

./vendor/bin/sail up -d laravel.test mysql

---

## License

MIT