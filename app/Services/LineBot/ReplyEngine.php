<?php
namespace App\Services\LineBot;

interface ReplyEngine{
    public function handle(LineMessage $lineMessage,LineReplyMessage &$lineReplyMessage);
}