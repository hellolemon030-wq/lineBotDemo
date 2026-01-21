<?php
namespace App\Services\Laravel;

use App\Services\LineBot\LineMessage;
use App\Services\LineBot\LineReplyMessage;
use App\Services\LineBot\ReplyEngine;
use SplPriorityQueue;

class CoreReplyEngine implements ReplyEngine{
    /**
     * @params SplPriorityQueue $engins;
     */
    protected SplPriorityQueue $engines;

    public function __construct(iterable $replyEngines){
        $this->engines = new SplPriorityQueue();
        foreach ($replyEngines as $item) {
            $this->addReplyEngine(
                $item['engine'],
                $item['priority'] ?? 0
            );
        }
    }

    public function addReplyEngine(ReplyEngine $replyEngine,$priority = 0){
        $this->engines->insert($replyEngine,$priority);
    }

    public function getCurrentEngines(){
        $queue = clone $this->engines;
        $result = [];

        foreach ($queue as $engine) {
            $result[] = $engine;
        }

        return $result;
    }

    public function handle(LineMessage $lineMessage, LineReplyMessage &$lineReplyMessage)
    {
        $engineQueue = clone $this->engines;
        foreach ($engineQueue as $replyEngine) {
            //Log::warning($replyEngine::class);
            $handleResult = $replyEngine->handle($lineMessage,$lineReplyMessage);

            //if return true， subsequent engines will NOT be executed.
            if ($handleResult === true) {
                break;
            }
        }
        return $lineReplyMessage;
    }
} 