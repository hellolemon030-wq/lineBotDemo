<?php

namespace App\Http\Controllers;

use App\Jobs\MessageJob;
use App\Services\Laravel\CoreEngine;
use App\Services\LineBot\LineMessage;
use App\Services\LineBot\LineReplyMessage;
use App\Services\LineBot\LineUser;

class IndexController extends Controller
{
    //
    public function test(){
        $engine = app()->get(CoreEngine::class);
        $bot = $engine->getBotByKey('');
        $userId = 'U1a51d24f26cef240cb89202a6f7a51a6';
        $lineMessage = new LineReplyMessage();
        $lineMessage->appendText('for test');
        $coreEngine = app()->get(CoreEngine::class);
        $lineUser = new LineUser();
        $lineUser->botKey = $bot->getKey();
        $lineUser->userId = $userId;
        $coreEngine->pushUserTextMessage($lineUser,'test for test');
        // $r = $bot->pushUserMessage($userId,$lineMessage);
        // var_dump($r);
        // $lineUser = new LineUser();
        // $lineUser->botKey = $bot->getKey();
        // $lineUser->userId = $userId;
    }

    public function msg(){
        $message = ['type'=>'typeTest','content'=>'contentTest'.now()];
        echo 'send message'.json_encode($message);
        MessageJob::dispatch($message);
    }
}
