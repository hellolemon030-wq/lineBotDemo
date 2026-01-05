<?php

namespace App\Console\Commands;

use App\Services\Laravel\CoreEngine;
use App\Services\Laravel\DBLineMessage;
use App\Services\Laravel\LineMessageManager;
use App\Services\LineBot\LineUser;
use Illuminate\Console\Command;

class LineMsgMg extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'line:msg {action} {bid?} {uid?} {msg?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Line message manager by cli';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $action = $this->argument('action');
        $bid    = $this->argument('bid');
        $uid    = $this->argument('uid');
        $msg    = $this->argument('msg');

        if($bid && $uid){
            $lineUser = new LineUser;
            $lineUser->botKey = $bid;
            $lineUser->userId = $uid;
        }

        $messageManager = app(LineMessageManager::class);

        switch($action){
            case 'showList':
                $list = $messageManager->getLatesedMessageByAllUser();
                $list = $list->reverse();
                foreach($list as $item){
                    $c = $this->msgShowFormat($item);
                    $this->info($c);
                }
                break;
            case 'showUserHistoryList':
                if(!$uid){
                    $this->error('UID cannot be null');
                    return;
                }
                $history = $messageManager->getUserHistoryList($lineUser);
                $history = $history->reverse();
                foreach($history as $item){
                    $c = $this->msgShowFormat($item);
                    $this->info($c);
                }
                break;
            case 'reply':
                if(!$uid || !$msg){
                    $this->error(' UID and message must');
                    return;
                }
                $coreEngine = app()->get(CoreEngine::class);
                $coreEngine->pushUserTextMessage($lineUser,$msg);
                $this->info("message to $uid has sended");
                break;

            default:
                $this->error('unknow command;');
        }
    }

    public function msgShowFormat($msg){
        $lineMessage = DBLineMessage::initUserMessageByDbModel($msg);

        $botId = $lineMessage->getBotKey() ?? '';
        $userId = $lineMessage->getUserId() ?? '';
        $timestamp = $lineMessage->getTimestamp() ? date('Y-m-d H:i:s', $lineMessage->getTimestamp()/1000) : '';
        $messageType = $lineMessage->getMessageType() ?? 'unknown';
        $content = '';
        $direction = $lineMessage->getDirection();

        if($direction){
            $content = json_encode($lineMessage->getMessageContent(),JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            $directionContent = $lineMessage->getDirection() ? 'push' : 'handle';
            if($lineMessage->getReplyToken()){
                $directionContent = 'reply by replyToken';
            }
        } else {
            $directionContent = $lineMessage->getDirection() ? 'push' : 'handle';
            switch ($messageType) {
                case 'text':
                    $content = $lineMessage->getText() ?? '';
                    break;
                case 'image':
                case 'video':
                case 'audio':
                case 'file':
                    $msgContent = $lineMessage->getMessageContent() ?? [];
                    $content = sprintf("[%s] %s", $messageType, $msgContent['originalContentUrl'] ?? '');
                    break;
                case 'sticker':
                    $msgContent = $lineMessage->getMessageContent() ?? [];
                    $content = sprintf("[%s] packageId:%s stickerId:%s", $messageType, $msgContent['packageId'] ?? '', $msgContent['stickerId'] ?? '');
                    break;
                case 'location':
                    $msgContent = $lineMessage->getMessageContent() ?? [];
                    $content = sprintf("[%s] %s (%s, %s)", $messageType, $msgContent['title'] ?? '', $msgContent['latitude'] ?? '', $msgContent['longitude'] ?? '');
                    break;
                default:
                    $content = $messageType;
                    break;
            }
        }

        // 生成命令行可复制的字符串（例如 reply 指令）
        $cmdCopy = sprintf("php artisan line:msg reply %s %s [content]", $lineMessage->getBotKey(), $lineMessage->getUserId());


        // 拼接显示
        $format = sprintf(
            "==Bot:%s User:%s == \n direction: %s \n Type:%s \n Content:%s \n timestamp: %s \n CMD: %s \n ",
            $botId,
            $userId,
            $directionContent,
            $messageType,
            $content,
            $timestamp,
            $cmdCopy
        );

        return $format;
    }
}
