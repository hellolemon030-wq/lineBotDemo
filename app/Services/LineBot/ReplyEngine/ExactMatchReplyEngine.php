<?php
namespace Apps\ervices\LineBot\ReplyEngine;

use App\Services\LineBot\LineMessage;
use App\Services\LineBot\ReplyEngine;

class ExactMatchReplyEngine extends ReplyEngine{
    public function handle(LineMessage $lineMessage)
    {
        throw new \Exception('Not implemented');
    }
}