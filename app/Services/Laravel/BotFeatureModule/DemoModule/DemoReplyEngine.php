<?php
namespace App\Services\Laravel\BotFeatureModule\DemoModule;

use App\Services\LineBot\LineMessage;
use App\Services\LineBot\LineReplyMessage;
use App\Services\LineBot\ReplyEngine;

class DemoReplyEngine implements ReplyEngine
{
    /**
     * Identifier of the module event.
     * This value is injected when the reply engine is created.
     */
    public int $demoModuleEventId;

    /**
     * Handle incoming LINE messages.
     *
     * In a real-world implementation, this method should:
     * - Verify that the event exists
     * - Check whether the event is still active or expired
     * - Return an appropriate response based on the event state
     */
    public function handle(LineMessage $lineMessage, LineReplyMessage &$lineReplyMessage)
    {
        $lineReplyMessage->appendText(
            'https://www.abcde.com/demoEvent/' . $this->demoModuleEventId
        );

        return $lineReplyMessage;
    }
}