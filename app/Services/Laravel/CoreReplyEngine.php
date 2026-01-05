<?php
namespace App\Services\Laravel;

use App\Services\LineBot\LineMessage;
use App\Services\LineBot\lineReplyMessage;
use App\Services\LineBot\ReplyEngine;
use Illuminate\Support\Facades\Log;
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

    public function handle(LineMessage $lineMessage, lineReplyMessage &$lineReplyMessage)
    {
        $engineQueue = clone $this->engines;
        foreach ($engineQueue as $replyEngine) {
            //Log::warning($replyEngine::class);
            $replyEngine->handle($lineMessage,$lineReplyMessage);

            if (count($lineReplyMessage->getMessages())) {
                break;
            }
        }
        return $lineReplyMessage;
    }
} 