<?php
namespace App\Services\Laravel\ReplyEngines;

use App\Services\LineBot\LineMessage;
use App\Services\LineBot\LineReplyMessage;
use App\Services\LineBot\ReplyEngine;

class DescriptionReplyEngine implements ReplyEngine{
    public function handle(LineMessage $lineMessage, LineReplyMessage &$lineReplyMessage)
    {

        $content = $lineMessage->getMessageContent()['text'] ?? '';

        $defaultText = "message has received;";
        if ($lineMessage->getMessageType() === LineMessage::MESSAGE_TYPE_TEXT) {
            $defaultText .= " content: " . $content;
            $lineReplyMessage->appendText($defaultText);
            return true;
        }
    }
}