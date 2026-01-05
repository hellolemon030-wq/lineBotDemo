<?php
namespace App\Services\Laravel;

use App\Models\MsgModel;
use App\Services\LineBot\LineMessage;
use App\Services\LineBot\LineUser;
use Carbon\Carbon;

class LineMessageManager{
    public function saveLineMessage(LineMessage $lineMessage){
        $message = new MsgModel();
        $message->bot_id  = $lineMessage->getBotKey();
        $message->source_type  = $lineMessage->getSourceType();
        $message->event_type = $lineMessage->getEventType();
        $message->event_id = $lineMessage->getEventId();
        $message->user_id = $lineMessage->getUserId();
        $message->platform_message_id    = $lineMessage->getMessageId(); 
        $message->direction    = $lineMessage->getDirection(); 
        $message->reply_token    = $lineMessage->getReplyToken(); 
        $message->message_type = $lineMessage->getMessageType();
        $message->content = json_encode($lineMessage->getMessageContent(), JSON_UNESCAPED_UNICODE);
        $message->raw_payload = json_encode($lineMessage->getRaw(), JSON_UNESCAPED_UNICODE);
        $message->is_read = 0; 
        $message->timestamp = $lineMessage->getTimestamp();

        $message->save(); 

        $insertedId = $message->id;
        return $insertedId;
    }

    public function showLineUserMessageHistory(LineUser $lineUser,$count = 10){
        $histories =  MsgModel::query()
            ->where('bot_id', $lineUser->botKey)
            ->where('user_id', $lineUser->userId)
            ->where('message_type', 'text')
            ->orderBy('id', 'desc')
            ->limit($count)
            ->get();
        foreach($histories as $history){

        }
    }

    public function getLineMessageById($id){
        return MsgModel::find($id);
    }

    public function updatePushStatus(DBLineMessage $dbLineMessage,$fromStatus,$toStatus){
        return MsgModel::where('id',$dbLineMessage->getDbId())
            ->where('direction',1)
            ->where('status',$fromStatus)
            ->update('status',$toStatus);
    }

    public function replyPushUpdate(DBLineMessage $dbLineMessage,$raw,$fromStatus,$toStatus){
        return MsgModel::where('id',$dbLineMessage->getDbId())
            ->where('direction',1)
            ->where('status',$fromStatus)
            ->update([
                'status'=>$toStatus,
                'raw_payload'=>$raw
            ]);
    }

    //
    /**
     * get message history;
     * @param int $count message history count;
     * @return \Illuminate\Database\Eloquent\Collection|MsgModel[]
     */
    public function getLatesedMessageByAllUser($count = 10){
        $latestMessages = MsgModel::where('direction', 0)
            ->orderByDesc('created_at')
            ->limit($count)
            ->get();
        return $latestMessages;
    }

    //
    /**
     * get user message history;
     *
     * @param LineUser $lineUser 
     * @param int $count 
     * @return \Illuminate\Database\Eloquent\Collection|MsgModel[]
     */
    public function getUserHistoryList(LineUser $lineUser,$count = 10){
        $latestMessages = MsgModel::where('bot_id',$lineUser->botKey)
            ->where('user_id',$lineUser->userId)
            ->orderByDesc('created_at')
            ->limit($count)
            ->get();
        return $latestMessages;
    }
}