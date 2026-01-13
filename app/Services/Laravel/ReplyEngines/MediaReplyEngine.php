<?php
namespace App\Services\Laravel\ReplyEngines;

use App\Services\LineBot\LineMessage;
use App\Services\LineBot\lineReplyMessage;
use App\Services\LineBot\ReplyEngine;

/**
 * Class MediaReplyEngine
 *
 * Handles non-text LINE messages (images, video, audio, stickers, etc.).
 * This engine is intended as a fallback for unsupported message types.
 *
 * Current behavior: appends a placeholder text indicating the message type.
 * Can be extended in the future to support media replies.
 */
class MediaReplyEngine implements ReplyEngine
{
    /**
     * Handle an incoming LINE message.
     *
     * @param LineMessage $lineMessage The received message from LINE
     * @param lineReplyMessage $lineReplyMessage The reply message object to append responses
     * @return bool True if the message is handled; false otherwise
     */
    public function handle(LineMessage $lineMessage, lineReplyMessage &$lineReplyMessage): bool
    {
        // Only non-text messages are handled by this engine
        if ($lineMessage->getMessageType() === LineMessage::MESSAGE_TYPE_TEXT) {
            return false;  // Do not handle text messages
        }

        // Append a default placeholder response
        $lineReplyMessage->appendText(
            'Message received. Type: ' . $lineMessage->getMessageType() . ". " .
            'This type of message is not currently supported by this reply engine.'
        );

        return true;
    }
}