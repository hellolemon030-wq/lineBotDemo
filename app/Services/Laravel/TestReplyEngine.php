<?php
namespace App\Services\Laravel;

use App\Services\LineBot\LineMessage;
use App\Services\LineBot\LineReplyMessage;
use App\Services\LineBot\ReplyEngine;

class TestReplyEngine implements ReplyEngine{

    public function handle(LineMessage $lineMessage, LineReplyMessage &$lineReplyMessage)
    {
        $content = $lineMessage->getMessageContent()['text'] ?? '';

        // 如果消息以 ai: 或 ai： 开头，直接返回，不处理
        if (preg_match('/^\s*ai[:：]/i', $content)) {
            return $lineReplyMessage;
        }

        $defaultText = "message has handled;";
        if ($lineMessage->getMessageType() === LineMessage::MESSAGE_TYPE_TEXT) {
            $defaultText .= " content: " . $content;
        } else {
            $defaultText .= " message type: " . $lineMessage->getMessageType();
        }
        $lineReplyMessage->appendText($defaultText);
        return $lineReplyMessage;
    }
}