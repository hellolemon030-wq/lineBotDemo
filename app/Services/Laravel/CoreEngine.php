<?php
namespace App\Services\Laravel;

use App\Jobs\LinePushReply;
use App\Services\LineBot\Bot;
use App\Services\LineBot\LineMessage;
use App\Services\LineBot\LineReplyMessage;
use App\Services\LineBot\LineUser;
use App\Services\LineBot\ReplyEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CoreEngine
{
    private $storeBotManager;
    private $lineMessageManager;
    private $replyEngine;

    public function __construct(
        StoreBotManager $storeBotManager,
        LineMessageManager $lineMessageManager,
        ReplyEngine $replyEngine)
    {
        $this->storeBotManager = $storeBotManager;
        $this->lineMessageManager = $lineMessageManager;
        $this->replyEngine = $replyEngine;
    }

    public function getStoreBotManager():StoreBotManager{
        return $this->storeBotManager;
    }

    public function getLineMessageManager():LineMessageManager{
        return $this->lineMessageManager;
    }

    public function getReplyEngine() : ReplyEngine {
        return $this->replyEngine;
    }

    /**
     * @param string $key
     * @return Bot
     */
    public function getBotByKey(string $key)
    {   
        return $this->getStoreBotManager()->getRuntimeBot($key);
    }

    public function httpHandle(Request $request, string $botKey = ''){
        $bot = $this->getBotByKey($botKey);
        if(empty($bot)){
            Log::warning('bot not exists;');
            return response('OK', 200);
        }
        // 验证请求签名
        $xLinesign = $request->header('X-Line-Signature') ?? '';
        $body = $request->getContent();
        if (!$bot->validateRequestSignature($xLinesign,$body)) {
            Log::warning('LINE Webhook varify fail', ['body' => $request->getContent(), 'botKey' => $botKey]);
            return response('Invalid signature', 400);
        } else {
            //Log::info('LINE Webhook valid success');
        }

        // message handle mode（true = async；false = rsync）
        $this->webhookMessageHandle($request, $bot, $this->isLineReplyPushMsgHandleDirectMode());

        return response('OK', 200);
    }

    /**
     * message handle
     * @param Request $request
     * @param Bot|null $bot
     * @return void
     */
    public function webhookMessageHandle(Request $request, ?Bot $bot = null): void
    {
        $botKey = $bot ? $bot->getKey() : null;
        $lineMessageManager = $this->getLineMessageManager();
        $lineMessages = LineMessage::fromWebhookRequest($request->getContent(), $botKey);

        $replyEngine = $this->getReplyEngine();

        foreach ($lineMessages as $message) {
            $lineMessageManager->saveLineMessage($message);
            $lineReplyMessage = new LineReplyMessage();
            $replyEngine->handle($message,$lineReplyMessage);

            $replyLineMessage = $message->generateReplyLineMessage($lineReplyMessage);
            $insertId = $lineMessageManager->saveLineMessage($replyLineMessage);

            $model = $this->getLineMessageManager()->getLineMessageById($insertId);
            $instance = DBLineMessage::initUserMessageByDbModel($model);

            $this->pushMessage($instance);
        }

        return;
    }

    // /**
    //  * 真正的业务逻辑处理
    //  * @param LineMessage $message
    //  * @param Bot|null $bot
    //  * @return void
    //  */
    // public function handleDetail(LineMessage $message, ?Bot $bot = null): void
    // {
    //     // 如果没有传Bot实例，通过 botKey 获取
    //     if (!$bot && $message->getBotKey()) {
    //         $bot = $this->getBotByKey($message->getBotKey());
    //     }

    //     $replyEngine = $this->getReplyEngine();
    //     $lineReplyMessage = new LineReplyMessage;
    //     $replyMessage = $replyEngine->handle($message,$lineReplyMessage);

    //     if ($bot) {
    //         $replyToken = $message->getReplyToken();
    //         $userId = $message->getUserId();
    //         if ($replyToken) {
    //             // 回复消息给触发事件的用户
    //             $bot->replyByToken($replyToken, $replyMessage);
    //         }
    //         //todo:other type;;
    //     }
    //     return;
    // }

    //send a text message to user;
    public function pushUserTextMessage(LineUser $lineUser,$text = ''){
        $lineReplyMessage = new LineReplyMessage;
        $lineReplyMessage->appendText($text);
        $lineMessage = DBLineMessage::generateSingleUserPushMessage($lineUser,$lineReplyMessage);
        $lineMessageManager = $this->getLineMessageManager();
        $id = $lineMessageManager->saveLineMessage($lineMessage);
        $saveLineMessageRecord = $lineMessageManager->getLineMessageById($id);
        $saveLineMessage = DBLineMessage::initUserMessageByDbModel($saveLineMessageRecord);
        $this->pushMessage($saveLineMessage);
    }

    public function pushMessage(LineMessage $lineMessage){
        $directMode = $this->isLineReplyPushMsgHandleDirectMode();
        if($directMode){
            $this->lineMessageReplyPushHandle($lineMessage);
        } else {
            LinePushReply::dispatch($lineMessage->toJobPayload());
        }
    }

    public function jobHandle($job){
        if($job['dbId']){
            $instance = DBLineMessage::fromJobPayload($job);
        } else {
            $instance = LineMessage::fromJobPayload($job);
        }
        $this->lineMessageReplyPushHandle($instance);
    }

    public function lineMessageReplyPushHandle(LineMessage $lineMessage){
        $botKey = $lineMessage->getBotKey();
        $bot = $this->getBotByKey($botKey);

        if($lineMessage->getReplyToken()){  //reply by replyToken
            $ret = $bot->replyByToken($lineMessage->getReplyToken(),$lineMessage->generateLineReplyMessage());
        } else {    //push msg;
            $ret = $bot->pushUserMessage($lineMessage->getUserId(),$lineMessage->generateLineReplyMessage());
        }
        if($lineMessage instanceof DBLineMessage){
            //write db;
            //todo: exception handle;
            $this->getLineMessageManager()->replyPushUpdate($lineMessage,$ret,0,2);
        }
    }


    //false->rsync
    public function isLineReplyPushMsgHandleDirectMode(){
        return env('LINE_MESSAGE_HANDLE_DIRECT_MODE');
    }
    
}