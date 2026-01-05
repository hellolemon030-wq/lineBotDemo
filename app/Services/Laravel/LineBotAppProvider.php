<?php
namespace App\Services\Laravel;

use App\Services\AiRobert\FileBaseAi;
use App\Services\LineBot\BotManager;
use App\Services\LineBot\ReplyEngine;
use Illuminate\Support\ServiceProvider;

class LineBotAppProvider extends ServiceProvider{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton(StoreBotManager::class);
        $this->app->singleton(LineMessageManager::class);
        $this->app->singleton(BotManager::class,StoreBotManager::class);
        $this->app->singleton(ReplyEngine::class,function(){
            $aiReplyEngine = new FileBaseAi();
            $coreReplyEngine = new CoreReplyEngine([
                ['engine' => new TestReplyEngine(), 'priority' => 8],
                ['engine' => $aiReplyEngine, 'priority' => 5],
            ]);
            $coreReplyEngine->addReplyEngine(new TestReplyEngine,4);
            return $coreReplyEngine;
        });
        $this->app->singleton(CoreEngine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}