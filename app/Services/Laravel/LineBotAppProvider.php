<?php
namespace App\Services\Laravel;

use App\Services\Laravel\BotFeatureModule\DemoModule\DemoModule;
use App\Services\Laravel\BotFeatureModule\ModuleManager;
use App\Services\Laravel\BotFeatureModule\WeatherModule;
use App\Services\Laravel\ReplyEngines\DescriptionReplyEngine;
use App\Services\Laravel\ReplyEngines\EasyAiReplyEngine;
use App\Services\Laravel\ReplyEngines\ExactMatchEngine;
use App\Services\Laravel\ReplyEngines\FuzzyMatchEngine;
use App\Services\Laravel\ReplyEngines\MediaReplyEngine;
use App\Services\LineBot\BotManager;
use App\Services\LineBot\ReplyEngine;
use App\Services\pegaservice\PegaReplyEngine;
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
            $coreReplyEngine->addReplyEngine(new PegaReplyEngine,101);
            return $coreReplyEngine;
        });

        /**
         * Feature module registration
         * New bot modules must be registered here
         * to participate in rule-based workflows.
         */
        $this->app->singleton(ModuleManager::class, function () {
            $moduleManager = new ModuleManager();

            $moduleManager->registerModule(DemoModule::class);    // Demo feature
            $moduleManager->registerModule(WeatherModule::class); // Weather feature

            return $moduleManager;
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