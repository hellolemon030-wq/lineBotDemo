# LINE Bot Demo (Laravel + Docker)

Laravel Sail を利用した、Docker ベースの LINE Bot バックエンドのデモプロジェクトです。  
最小限の設定で即座に起動でき、複数 Bot 管理、自動応答エンジン、非同期処理、CLI 管理機能を備えた開発者向けの構成となっています。

## 主な特徴（Core Features）

1. **すぐに使える**  
   リポジトリをクローンし、`.env` を設定するだけで起動可能です。

2. **マルチ Bot 対応**  
   データベース上で複数の LINE Bot を一元管理できます。

3. **拡張可能な自動応答エンジン**  
   優先度に基づいて、カスタム応答エンジンや機能モジュールを柔軟に追加できます。

4. **非同期処理対応**  
   reply / push API を非同期で処理することで、コアサービスの安定性を確保しています。

5. **CLI 管理ツール**  
   LINE Bot の登録、応答ルール管理、メッセージ操作をコマンドラインから実行できます。

6. **アクセストークンの自動管理**  
   楽観的ロックを用いて、LINE のアクセストークンを自動更新します。

7. **メッセージ履歴管理**  
   すべての受信メッセージをデータベースに保存し、後続処理や分析に利用できます。

---

## 必要環境 (Requirements)

- Docker & Docker Compose
- Composer

---

## セットアップ

### 1. 依存関係のインストール

```bash
composer install
```

### 2. 環境設定

```bash
cp .env.example .env
```

.env を編集して以下を設定：

```bash
LINE_CHANNEL_ID=            # デフォルトの LINE Bot チャンネルID
LINE_CHANNEL_SECRET=        # デフォルトの LINE Bot チャンネルシークレット
LINE_MESSAGE_HANDLE_DIRECT_MODE=false
```

> LINE_MESSAGE_HANDLE_DIRECT_MODE を true に設定すると、受信メッセージの処理は 非同期モード で行われます。
> この場合、返信やプッシュ API 呼び出しは Laravel キューワーカー経由で処理され、Webhook の負荷を軽減し、安定性を向上させます。
> 設定後、手動でキューワーカーを起動してください:./vendor/bin/sail artisan queue:work

### 3. Docker コンテナの起動

```bash
./vendor/bin/sail up -d
```

### 4. アプリケーションの初期化

```bash
./vendor/bin/sail artisan key:generate  
./vendor/bin/sail artisan migrate
```

### 5. システムヘルスチェック

```bash
./vendor/bin/sail artisan line:docker
```

データベース、キュー、Bot システムの状態をチェックします。

---

## LINE Bot 設定

### LINE Bot の登録（マルチボット対応）

./vendor/bin/sail artisan linebot:mg add [LINE_BOT_ID] [LINE_BOT_SECRET]

例:

```bash
./vendor/bin/sail artisan linebot:mg add 1234567890 your_bot_secret
```

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

👉 詳細なアーキテクチャおよび使用方法については、以下を参照してください。
   [LINE Bot Reply Engine Architecture & Usage](app/Services/Laravel/BotFeatureModule/readme.md)


返信は LINE API を通じて、同期または非同期で送信されます (.env 設定による):

    LINE_MESSAGE_HANDLE_DIRECT_MODE=false   # 同期モード（デフォルト）
    LINE_MESSAGE_HANDLE_DIRECT_MODE=true    # 非同期モード（キューワーカー起動必須）

    > When asynchronous mode is enabled, API calls (reply/push) are handled via Laravel queue workers.
    > Start the worker after configuration:

```bash
./vendor/bin/sail artisan queue:work
```
---

## メッセージ管理 (CLI)

### 受信メッセージ一覧表示

```bash
./vendor/bin/sail artisan line:msg showList
```

### ユーザーへの返信

./vendor/bin/sail artisan line:msg reply [LINE_BOT_ID] [USER_ID] [CONTENT]

例:

```bash
./vendor/bin/sail artisan line:msg reply 1234567890 Uxxxxxxxx "Hello from CLI"
```

---

## 開発

コンテナ停止:

```bash
./vendor/bin/sail down
```

サービス起動::

```bash
./vendor/bin/sail up -d
```

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

## 注意事項

- アクセストークンはデータベースに保存され、自動更新されます
- トークン更新は楽観的ロックで処理
- HTTP 401 発生時は自動更新
- HTTP 403 発生時は権限エラーの可能性があり、手動更新が必要な場合があります
---

## 使用例（Usage Example）

このセクションでは、本プロジェクトを利用するための  
**最小構成かつ一連の流れ** を紹介します。

LINE Bot の登録から、CLI を用いたキーワード自動応答の設定まで、  
**実際のコマンドと具体例** を用いてエンドツーエンドで説明します。

---

### Step 1. LINE Bot を作成し、システムへ登録する
![alt text](image-1.png)

以下の例では、次の情報を使用します。

- Channel ID: `1234567890`
- Channel Secret: `qwertyuiopasdfghjkl`

次の CLI コマンドを実行して、LINE Bot を本システムに登録します。
```bash
# NOTE:
# 有効な Channel ID / Channel Secret を指定する必要があります。
# このコマンドは同時にアクセストークンの取得も行います。
# 認証情報が正しくない場合、Bot は登録されません。
./vendor/bin/sail php artisan linebot:mg add 1234567890 qwertyuiopasdfghjkl
```

登録後、LINE Developers Console にて Webhook URL を以下の形式で設定してください。

https://your-domain.com/webhook/{channelId}

![alt text](image-2.png)

### Step 2. シンプルなキーワード自動応答ルールを追加（完全一致）
ユーザーが テスト と送信した場合に、
テストは成功です。 と返信するルールを追加します。
```bash
# 完全一致（Exact Match）の自動応答ルールを追加
./vendor/bin/sail php artisan line:replyRule add 1234567890 exact 'テスト' common 'テストは成功です。'
# 成功すると、CLI に「Reply rule added successfully.」と表示されます。
```
![alt text](image-3.png)

### Step 3. キーワードを機能モジュールに紐付ける（Weather Module の例）
本デモプロジェクトには、特定地域の天気情報を返す
WeatherModule が含まれています。

まず、指定した Bot で利用可能なモジュールイベント一覧を表示します。
```bash
./vendor/bin/sail php artisan line:replyRule showModuleEventList 1234567890
```
出力例：
```bash
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
AddCommand をコピーしてキーワード部分を変更することで、
モジュール連携ルールを簡単に追加できます。

例：
ユーザーが 大阪天気 と送信した場合に、大阪の天気情報を返します。
```bash
# ./vendor/bin/sail php artisan line:replyRule add [botId] exact [keyWord] module [ModuleTag] [EventId]
./vendor/bin/sail php artisan line:replyRule add 1234567890 exact '大阪天気' module 'App\Services\Laravel\BotFeatureModule\WeatherModule' osaka
```
![alt text](image-4.png)


### Step 4. あいまい一致（Fuzzy Match）ルールとルール管理

ユーザーのメッセージに キーワード が含まれている場合に、
あらかじめ定義したメッセージを返信するルールを追加します。
```bash
# ./vendor/bin/sail php artisan line:replyRule add [botId] fuzzy [keyWord] common [reply Message]
./vendor/bin/sail php artisan line:replyRule add 1234567890 fuzzy 'キーワード' common 'メッセージの中にメッセージの中に「キーワード」が含まれています。'
```
![alt text](image-6.png)

既存ルールの管理も CLI から行えます。
```bash
# 現在登録されている自動応答ルールを一覧表示
./vendor/bin/sail php artisan line:replyRule show 1234567890

# RuleId を指定してルールを削除
./vendor/bin/sail php artisan line:replyRule del 1234567890 [RuleId]
```

---
この使用例では、以下の一連の基本フローをカバーしています。
- INE Bot の登録
- Webhook の設定
- 完全一致 / あいまい一致の自動応答設定
- 機能モジュールとのキーワード連携
- CLI によるルール管理

---

## Possible Extensions / Design Considerations

以下は「今すぐ実装する機能」ではなく、  
現在の設計方針およびアーキテクチャから **自然に発展可能な拡張方向** を示しています。

※ 本プロジェクトはそのままデプロイして利用することも可能ですが、  
現時点ではデモ実装であるため、一部機能において性能最適化を重視していません。  
商用利用を検討する場合は、用途に応じて機能面・運用面のリスクを各自で評価してください。

本プロジェクトは、**二次開発や機能拡張を前提とした構成**を意識し、  
プラグイン指向かつ疎結合なアーキテクチャを採用しています。  
各機能は責務単位で分離され、明確なインターフェースを通じて連携するため、  
既存の実装を大きく変更せずに、段階的な改善・差し替えが可能です。

---

### 1. キーワード／あいまいマッチングの最適化

現在は、ルールをデータベースからロードし逐次評価する  
シンプルな実装を採用しています。

将来的には、以下のような手法を  
**独立したモジュールとして差し替える**ことが可能です。

- インデックス化による高速検索
- キャッシュ戦略の導入
- ベクトル検索等を用いた高度なあいまい検索

---

### 2. Web ベース管理 UI（CLI と同等機能）

現在 CLI で提供している以下の管理機能を、  
Web UI として提供することが可能です。

- Bot 管理
- 返信ルール管理
- モジュール管理

内部 API は共通化されているため、  
UI レイヤーのみを追加する構成を想定しています。

---

### 3. LINE アプリ内ブラウザ／ユーザー認証の抽象化

LIFF（LINE Front-end Framework）および  
LINE Login（OAuth 2.0 / OpenID Connect）を利用し、

- LINE アプリ内ブラウザ上での Web フロー
- ユーザー識別・認証情報の取得

を共通 API としてラップすることで、  
単なるメッセージ返信にとどまらない業務フロー拡張を可能にします。

---

### 4. SaaS 向け権限・課金設計

将来的な SaaS 展開を見据え、  
サブスクリプション階層に応じて、以下の制御が可能です。

- 管理可能な Bot 数
- 利用可能な Module
- 高度な機能の提供範囲

※ 実際の制御内容はこれらに限定されません。

---

### 5. オープンなモジュールアーキテクチャ

`ModuleBase` を安定化し、以下を提供することで  
外部開発者による機能拡張を促進できます。

- モジュール開発ガイド
- 共通ユーティリティ／抽象インターフェース
- 再利用可能な基盤機能

---

これらは、大規模な再設計を行わずとも、  
本プロジェクトを **プラットフォームとして発展させるための現実的な拡張構想** です。  
実装可能性の高いものから、段階的に推進できると考えています。

#### （参考）将来的構想：モジュールエコシステム

現時点では構想段階ですが、  
設計次第では以下のような発展も考えられます。

- 開発者が Module を公開
- 利用者が導入・購入できるマーケットプレイス構想


