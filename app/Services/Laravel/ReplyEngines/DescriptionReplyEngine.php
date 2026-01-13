<?php
namespace App\Services\Laravel\ReplyEngines;

use App\Services\LineBot\LineMessage;
use App\Services\LineBot\lineReplyMessage;
use App\Services\LineBot\ReplyEngine;

class DescriptionReplyEngine implements ReplyEngine{
    public function handle(LineMessage $lineMessage, lineReplyMessage &$lineReplyMessage)
    {

        $content = $lineMessage->getMessageContent()['text'] ?? '';

        $defaultText = "message has received;";
        if ($lineMessage->getMessageType() === LineMessage::MESSAGE_TYPE_TEXT) {
            $defaultText .= " content: " . $content;
            $lineReplyMessage->appendText($defaultText);

            $description = <<<DOT
                This is a demo LINE Bot.

                Auto-reply engines are executed in the following order.
                Once a rule is matched, subsequent engines will NOT be executed.

                0. AI test engine (experimental)
                - A very simple AI-based text matching engine for development testing only.
                - It uses lightweight vector-based matching and is NOT intended to be accurate.
                - If a text message starts with "ai:" or "ai：" (e.g. "ai：学费"),
                the message will be handled by this engine.

                1. Exact text match
                - The message must match the keyword exactly.
                - Currently supported keywords:
                • 東京天気
                • 大阪天気

                2. Fuzzy text match
                - The message must contain a predefined keyword.
                - Current fuzzy keywords:
                • fuzzy (e.g. "asdf fuzzy zxcv" will match)
                • 使用方法

                3. Non-text message handling
                - If the message is not a text type (image, video, etc.),
                it will be handled by the media reply engine.

                4. Fallback response
                - If none of the above rules match, this message will be returned.
            DOT;
            $lineReplyMessage->appendText($description);
            //todo:
        }
        return true;
    }
}