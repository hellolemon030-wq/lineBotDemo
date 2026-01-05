# LINE Bot Demo (Laravel + Docker)

Laravel Sail を使用した Docker ベースの LINE Bot バックエンドです。
最小限の設定で即起動可能で、複数 Bot 対応・自動応答エンジン・非同期処理・CLI管理が揃った開発向けデモです。

**主な特徴 (Core Features)**

1.	すぐに使える – リポジトリをクローンして .env を設定するだけで起動可能。
2.	マルチ Bot 対応 – データベースで複数の LINE Bot を管理。
3.	拡張可能な自動応答エンジン – 優先度に応じてカスタムエンジンを追加可能。
4.	非同期処理対応 – reply/push API を非同期で処理し、コアサービスの安定性を確保。
5.	CLI ツール – LINE Bot の管理やメッセージ操作がコマンドラインで可能。
6.	アクセストークン自動管理 – 楽観的ロックでトークンを自動更新。
7.	メッセージ履歴管理 – すべての受信メッセージをデータベースに保存。

---

## 必要環境 (Requirements)

- Docker & Docker Compose
- Composer

---

## セットアップ

### 1. 依存関係のインストール

composer install

### 2. 環境設定

cp .env.example .env

.env を編集して以下を設定：

LINE_CHANNEL_ID=            # デフォルトの LINE Bot チャンネルID
LINE_CHANNEL_SECRET=        # デフォルトの LINE Bot チャンネルシークレット
LINE_MESSAGE_HANDLE_DIRECT_MODE=false

> LINE_MESSAGE_HANDLE_DIRECT_MODE を true に設定すると、受信メッセージの処理は 非同期モード で行われます。
> この場合、返信やプッシュ API 呼び出しは Laravel キューワーカー経由で処理され、Webhook の負荷を軽減し、安定性を向上させます。
> 設定後、手動でキューワーカーを起動してください:./vendor/bin/sail artisan queue:work

### 3. Docker コンテナの起動

./vendor/bin/sail up -d

### 4. アプリケーションの初期化

./vendor/bin/sail artisan key:generate  
./vendor/bin/sail artisan migrate

### 5. システムヘルスチェック

./vendor/bin/sail artisan line:docker

データベース、キュー、Bot システムの状態をチェックします。

---

## LINE Bot 設定

### LINE Bot の登録（マルチボット対応）

./vendor/bin/sail artisan linebot:mg add [LINE_BOT_ID] [LINE_BOT_SECRET]

例:

./vendor/bin/sail artisan linebot:mg add 1234567890 your_bot_secret

### Webhook 設定

デフォルト Bot と複数 Bot の両方に対応しています。

#### デフォルト Bot

Webhook URL に Bot ID が指定されていない場合、.env のデフォルト Bot を使用:

https://your-domain/webhook

#### 複数 Bot

システムに Bot を追加した後、Webhook URL に Bot キーを指定できます:

https://your-domain/webhook/{BOT_KEY}

例:

https://your-domain/webhook/1234567890

> Bot は最終的に Channel 認証情報で特定されます。URL パラメータは設定済み Bot を選択するためのものです。

---

## 自動応答エンジン (AutoReply Engine)

1.	CoreEngine::webhook が受信リクエストを処理。
2.	Bot とトークンを HMAC-SHA256 で検証。
3.	受信メッセージをデータベースに保存。
4.	AutoReply エンジンは優先度順に処理:
   - **Simple Reply Engine**  (デモ用, 優先度変更可能)
   - **File/AI Reply Engine**　(デモ用, AI/ファイルベース)
   - ...(実務に合わせてカスタムエンジンを追加可能)
5.	優先度処理: いずれかのエンジンがメッセージを処理（返信を追加）した場合、それ以降のエンジンは処理されません。
6.	設定: app/Providers/LineBotAppProvider.php でエンジンを追加・優先度変更可能。

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
> ⚠️ デモ用の設定です。実運用では、自社ビジネスロジックに応じてエンジンを開発・調整してください。
7. 返信は LINE API を通じて、同期または非同期で送信されます (.env 設定による):
    LINE_MESSAGE_HANDLE_DIRECT_MODE=false   # 同期モード（デフォルト）
    LINE_MESSAGE_HANDLE_DIRECT_MODE=true    # 非同期モード（キューワーカー起動必須）

    > When asynchronous mode is enabled, API calls (reply/push) are handled via Laravel queue workers.
    > Start the worker after configuration:

    ./vendor/bin/sail artisan queue:work
---

## メッセージ管理 (CLI)

### 受信メッセージ一覧表示

./vendor/bin/sail artisan line:msg showList

### ユーザーへの返信

./vendor/bin/sail artisan line:msg reply [LINE_BOT_ID] [USER_ID] [CONTENT]

例:

./vendor/bin/sail artisan line:msg reply 1234567890 Uxxxxxxxx "Hello from CLI"

---

## 開発

コンテナ停止:

./vendor/bin/sail down

サービス起動::

./vendor/bin/sail up -d

---

## モジュール関係図 (ASCII)

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
      +-- Trigger AutoReply Engines  <-- Core: handle messages via priority-based engines
      |       |
      |       +-- TestReplyEngine       (demo engine, priority adjustable)
      |       |
      |       +-- FileBaseAi Engine     (demo engine, AI/file-based)
      |       |
      |       +-- ......                (custom engines: strongly recommended to implement per actual business logic via ReplyEngine)
      │
      └─ Send reply (sync / async) → LINE API

[CLI Tools]
      │
      ├─ linebot:mg  (Add/Modify Bots)
      │
      └─ line:msg    (View/Reply Messages)

[Jobs / Queue Workers]
      │
      └─ Handle async push/reply tasks to LINE API
```

---

## 注意事項

- アクセストークンはデータベースに保存され、自動更新されます
- トークン更新は楽観的ロックで処理
- HTTP 401 発生時は自動更新
- HTTP 403 発生時は権限エラーの可能性があり、手動更新が必要な場合があります
---