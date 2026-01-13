<?php
namespace App\Services\Laravel\ReplyEngines;

use App\Services\AiRobert\FileBaseAi;
use App\Services\LineBot\LineMessage;
use App\Services\LineBot\LineReplyMessage;
use Illuminate\Support\Facades\Log;

class EasyAiReplyEngine extends FileBaseAi{
    public function handle(LineMessage $lineMessage, LineReplyMessage &$lineReplyMessage)
    {
        if ($lineMessage->getMessageType() === LineMessage::MESSAGE_TYPE_TEXT) {
            $content = $lineMessage->getMessageText();
            if (preg_match('/^\s*ai[:：]/i', $content)) {
                parent::handle($lineMessage,$lineReplyMessage);
                return true;
            } else {
                return false;
            }
        }
    }
}