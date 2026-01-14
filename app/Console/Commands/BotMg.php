<?php

namespace App\Console\Commands;

use App\Services\LineBot\BotManager;
use Illuminate\Console\Command;

class BotMg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'linebot:mg 
                            {ope : query|add|del|modify|list} 
                            {key? : Bot access key} 
                            {secret? : Bot secret}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bot manager CLI; manage Bot instances';

    /**
     * Execute the console command.
     */
    public function handle(BotManager $botManager)
    {
        $ope = $this->argument('ope');
        $key = $this->argument('key');
        $secret = $this->argument('secret');

        switch ($ope) {
            case 'query':
                if (!$key) {
                    $this->error('Key is required for query.');
                    return;
                }
                $bot = $botManager->queryBot($key);
                if ($bot) {
                    $this->info("Bot found: key={$bot->getKey()}, secret={$bot->getSecret()}, token={$bot->getAccessToken()}");
                } else {
                    $this->warn("Bot not found: key={$key}");
                }
                break;

            case 'add':
                if (!$key || !$secret) {
                    $this->error('Key and secret are required for add.');
                    return;
                }
                $bot = $botManager->addBot($key, $secret);
                $this->info("Bot added: key={$bot->getKey()}, secret={$bot->getSecret()}");
                break;

            case 'del':
                if (!$key) {
                    $this->error('Key is required for delete.');
                    return;
                }
                $deleted = $botManager->delBotByAccessKey($key);
                if ($deleted) {
                    $this->info("Bot deleted: key={$key}");
                } else {
                    $this->warn("Bot not found or delete failed: key={$key}");
                }
                break;

            case 'modify':
                if (!$key || !$secret) {
                    $this->error('Key and secret are required for modify.');
                    return;
                }
                $bot = $botManager->modifyBot($key, $secret);
                if ($bot) {
                    $this->info("Bot modified: key={$bot->getKey()}, secret={$bot->getSecret()}");
                } else {
                    $this->warn("Bot not found or modify failed: key={$key}");
                }
                break;

            case 'list':
                $bots = $botManager->listBots();
                if (!$bots) {
                    $this->warn("No bots found.");
                } else {
                    foreach ($bots as $b) {
                        $this->line("key={$b->getKey()}, secret={$b->getSecret()}, token={$b->getAccessToken()}");
                    }
                }
                break;

            default:
                $this->error("Unknown operation: {$ope}");
                break;
        }
    }
}