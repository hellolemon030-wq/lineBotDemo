<?php
namespace App\Services\LineBot;

use LINE\Clients\MessagingApi\Model\TextMessage;
use LINE\Clients\MessagingApi\Model\ImageMessage;
use LINE\Clients\MessagingApi\Model\VideoMessage;
use LINE\Clients\MessagingApi\Model\AudioMessage;
use LINE\Clients\MessagingApi\Model\FlexMessage;
use LINE\Clients\MessagingApi\Model\StickerMessage;
use LINE\Clients\MessagingApi\Model\LocationMessage;
use LINE\Clients\MessagingApi\Model\Message;
use LINE\Clients\MessagingApi\Model\TemplateMessage;
use LINE\Webhook\Model\FileMessageContent;

/**
 * todo: rename
 */
class LineReplyMessage
{
    /** @var Message[] */
    protected array $messages = [];

    /**
     * text content
     */
    public function appendText(string $text): self
    {
        $this->messages[] = new TextMessage(['text' => $text]);
        return $this;
    }

    /**
     * image
     */
    public function appendImage(string $originalUrl, string $previewUrl): self
    {
        $this->messages[] = new ImageMessage([
            'originalContentUrl' => $originalUrl,
            'previewImageUrl'    => $previewUrl,
        ]);
        return $this;
    }

    /**
     * video
     */
    public function appendVideo(string $originalUrl, string $previewUrl): self
    {
        $this->messages[] = new VideoMessage([
            'originalContentUrl' => $originalUrl,
            'previewImageUrl'    => $previewUrl,
        ]);
        return $this;
    }

    /**
     * audio
     */
    public function appendAudio(string $url, int $duration): self
    {
        $this->messages[] = new AudioMessage([
            'originalContentUrl' => $url,
            'duration'           => $duration,
        ]);
        return $this;
    }

    /**
     * sticker
     */
    public function appendSticker(string $packageId, string $stickerId): self
    {
        $this->messages[] = new StickerMessage([
            'packageId' => $packageId,
            'stickerId' => $stickerId,
        ]);
        return $this;
    }

    /**
     * location
     */
    public function appendLocation(string $title, string $address, float $latitude, float $longitude): self
    {
        $this->messages[] = new LocationMessage([
            'title'     => $title,
            'address'   => $address,
            'latitude'  => $latitude,
            'longitude' => $longitude,
        ]);
        return $this;
    }

    /**
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    static public function getMessageType($message){
        switch (true) {
            case $message instanceof TextMessage:
                return LineMessage::MESSAGE_TYPE_TEXT;
            case $message instanceof ImageMessage:
                return LineMessage::MESSAGE_TYPE_IMAGE;
            case $message instanceof VideoMessage:
                return LineMessage::MESSAGE_TYPE_VIDEO;
            case $message instanceof AudioMessage:
                return LineMessage::MESSAGE_TYPE_AUDIO;
            case $message instanceof LocationMessage:
                return LineMessage::MESSAGE_TYPE_LOCATION;
            case $message instanceof StickerMessage:
                return LineMessage::MESSAGE_TYPE_STICKER;
            // case $message instanceof FileMessageContent:
            //     return LineMessage::MESSAGE_TYPE_FILE;
            // case $message instanceof TemplateMessage:
            //     return LineMessage::MESSAGE_TYPE_TEMPLATE;
            // case $message instanceof FlexMessage:
            //     return LineMessage::MESSAGE_TYPE_FLEX;
            default:
                return 'unknown';
        }
    }
}