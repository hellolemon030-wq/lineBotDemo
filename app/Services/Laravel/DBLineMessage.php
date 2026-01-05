<?php
namespace App\Services\Laravel;

use App\Models\MsgModel;
use App\Services\LineBot\LineMessage;
use App\Services\LineBot\LineReplyMessage;
use App\Services\LineBot\LineUser;
use Illuminate\Support\Str;

class DBLineMessage extends LineMessage{
    protected int $dbId;

    static public function initUserMessageByDbModel(MsgModel $model){
        if($model->source_type != LineMessage::SOURCE_TYPE_USER){
            throw new \Exception('');
        }

        $instance = new self();
        $instance->botKey = $model->bot_id;
        $instance->sourceType = $model->source_type;
        $instance->sourceId = $model->user_id;
        $instance->userId = $model->user_id;
        $instance->direction = $model->direction;
        $instance->eventType = $model->message_type;
        $instance->messageType = $model->message_type;
        $instance->messageContent = json_decode($model->content, true);
        $instance->raw = json_decode($model->raw_payload, true);
        $instance->eventId = $model->event_id ?? (string) Str::uuid();
        $instance->dbId = $model->id;
        $instance->replyToken = $model->reply_token;
        $instance->timestamp = $model->timestamp;

        return $instance;
    }

    static public function generateSingleUserPushMessage(LineUser $lineUser,LineReplyMessage $lineReplyMessage):LineMessage{
        $messages = $lineReplyMessage->getMessages();
        $messageArray = [];
        foreach($messages as $message){
            $messageArray[] = (array)$message->jsonSerialize();
        }
        $instance = new LineMessage();
        $instance->botKey = $lineUser->botKey;
        $instance->sourceType = self::SOURCE_TYPE_USER;
        $instance->sourceId = $lineUser->userId;
        $instance->userId = $lineUser->userId;
        $instance->direction = 1;
        $instance->messageContent = (array)$messageArray;
        $instance->eventId = (string) Str::uuid();
        $instance->timestamp = (int) (microtime(true) * 1000);
        return $instance;
    }

    public function getDbId(){
        return $this->dbId;
    }

    public function toJobPayload(): array
    {
        $return = parent::toJobPayload();
        $return['dbId'] = $this->dbId;
        return $return;
    }
    static public function fromJobPayload(array $payload)
    {
        $instance = new self();

        $parent = parent::fromJobPayload($payload);

        foreach (get_object_vars($parent) as $k => $v) {
            $instance->$k = $v;
        }

        $instance->dbId = $payload['dbId'] ?? null;

        return $instance;
    }
}