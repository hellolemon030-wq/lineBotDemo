<?php

namespace App\Console\Commands;

use App\Services\Laravel\CoreReplyEngine;
use App\Services\Laravel\StoreBotManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class LineDocker extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'line:docker';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Line Bot Demo Docker health check';

    /**
     * Execute the console command.
     */
    public function handle(StoreBotManager $storeBotManager)
    {
        //
        try {
            //$this->checkApp();
            $this->checkDatabase();
            $this->checkQueue();
            $this->checkCache();
            $this->checkBot();
            $this->checkCoreEngine();

            $this->newLine();
            $this->info('[SUCCESS] Bot system is healthy 🚀');
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('[FAIL] Bot system is NOT healthy ❌');
            $this->line($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function checkApp(){
        $this->line('▶ Checking application...');

        if (!app()->bound('config')) {
            throw new \Exception('Config not loaded');
        }

        $this->info('  ✔ Application booted');
    }

    protected function checkDatabase(): void
    {
        $this->line('▶ Checking database...');

        try {
            DB::select('SELECT 1');
        } catch (\Throwable $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }

        $this->info('  ✔ Database connection OK');
    }

    protected function checkQueue(): void
    {
        $this->line('▶ Checking queue...');

        $driver = Config::get('queue.default');

        if ($driver === 'database') {
            if (!DB::getSchemaBuilder()->hasTable('jobs')) {
                throw new \Exception('Queue driver is database but jobs table not found');
            }
            $this->info('  ✔ Queue (database) ready');
            return;
        }

        if ($driver === 'redis') {
            Redis::connection()->ping();
            $this->info('  ✔ Queue (redis) ready');
            return;
        }

        throw new \Exception("Unsupported queue driver: {$driver}");
    }

    protected function checkCache(): void
    {
        $this->line('▶ Checking cache...');

        try {
            Cache::put('__health_check', 'ok', 5);
            $value = Cache::get('__health_check');
        } catch (\Throwable $e) {
            throw new \Exception('Cache connection failed: ' . $e->getMessage());
        }

        if ($value !== 'ok') {
            throw new \Exception('Cache read/write failed');
        }

        $this->info('  ✔ Cache system OK');
    }

    protected function checkBot(): void
    {
        $this->line('▶ Checking LINE bot configuration...');

        $botManager = app(\App\Services\Laravel\StoreBotManager::class);
        $bot = $botManager->getDefaultBot();

        /**
         * 1. bot（DB / env）
         */
        if (!$bot) {
            $this->warn('  ✖ No bot found');

            $this->line("\nPlease configure LINE bot credentials:");

            $envKeys = [
                'LINE_BOT_CHANNEL_ID',
                'LINE_BOT_CHANNEL_SECRET',
            ];

            foreach ($envKeys as $key) {
                $value = env($key);
                $this->line(sprintf(
                    '  %-30s %s',
                    $key,
                    $value ? '[OK]' : '[MISSING]'
                ));
            }

            throw new \Exception(
                'LINE bot is not configured. ' .
                'Please set env variables.'
            );
        }

        $this->info('  ✔ Bot loaded (ID: ' . $bot->getKey() . ')');

        /**
         * 2.  API token varify
         */
        $this->line('  ▶ Verifying channel access token...');

        try {
            $result = $bot->checkTokenHealth();
        } catch (\Throwable $e) {
            throw new \Exception(
                'LINE API request failed: ' . $e->getMessage()
            );
        }
        
        if (
            !is_array($result)
            || !($result['ok'] ?? false)
        ) {
            throw new \Exception(
                'LINE bot check failed: ' .
                json_encode($result, JSON_UNESCAPED_UNICODE)
            );
        }

        $profile = $result['data'] ?? [];
        $this->info(sprintf(
            '  ✔ LINE bot reachable (%s)',
            $profile['displayName'] ?? 'unknown'
        ));

        if (!empty($profile['userId'])) {
            $this->line('    └ userId: ' . $profile['userId']);
        }
    }

    protected function checkCoreEngine(): void
    {
        $this->line('▶ Checking CoreEngine...');

        $core = app(\App\Services\Laravel\CoreEngine::class);

        if (!$core) {
            throw new \Exception('CoreEngine not bound');
        }

        $this->info('  ✔ CoreEngine resolved');

        // 1. Line message handle mode;
        $this->line(
            '  ▶ Message handling mode ,is direct ? : ' . ($core->isLineReplyPushMsgHandleDirectMode() ? 'yes' : 'no')
        );

        // 2. Reply Engine Manager
        $this->line('  ▶ Checking reply engine pipeline...');

        $replyEngine = $core->getReplyEngine();

        if (!$replyEngine) {
            $this->warn('  ⚠ CoreReplyEngine not bound');
            return;
        }

        if($replyEngine instanceof CoreReplyEngine){
            $engines = $replyEngine->getCurrentEngines();
            if (empty($engines)) {
                $this->warn('  ⚠ No reply engines registered');
                return;
            }
            $this->info(
                sprintf(
                    '  ✔ %d reply engine(s) loaded (ordered by priority)',
                    count($engines)
                )
            );
            foreach ($engines as $engine) {
                $this->line(sprintf(
                    '    %s',
                    get_class($engine)
                ));
            }
        }
    }


}
