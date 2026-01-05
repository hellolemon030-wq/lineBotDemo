<?php
namespace App\Services\LineBot;

use Exception;
use JsonException;

/**
 * Class LineMessage
 *  LINE Messaging API object
 */
class LineMessage
{
    // -------------------- event type --------------------
    public const EVENT_FOLLOW = 'follow';
    public const EVENT_UNFOLLOW = 'unfollow';
    public const EVENT_MESSAGE = 'message';
    public const EVENT_JOIN = 'join';
    public const EVENT_LEAVE = 'leave';
    public const EVENT_POSTBACK = 'postback';
    public const EVENT_BEACON = 'beacon';
    public const EVENT_MEMBER_JOINED = 'memberJoined';
    public const EVENT_MEMBER_LEFT = 'memberLeft';
    public const EVENT_ACCOUNT_LINK = 'accountLink';
    public const EVENT_RICH_MENU_SWITCHED = 'richMenuSwitch';
    public const EVENT_VIDEO_PLAY_COMPLETE = 'videoPlayComplete';

    // -------------------- original type --------------------
    public const SOURCE_TYPE_USER  = 'user';
    public const SOURCE_TYPE_GROUP = 'group';
    public const SOURCE_TYPE_ROOM  = 'room';

    // -------------------- message type --------------------
    public const MESSAGE_TYPE_TEXT = 'text';
    public const MESSAGE_TYPE_IMAGE = 'image';
    public const MESSAGE_TYPE_VIDEO = 'video';
    public const MESSAGE_TYPE_AUDIO = 'audio';
    public const MESSAGE_TYPE_LOCATION = 'location';
    public const MESSAGE_TYPE_STICKER = 'sticker';
    public const MESSAGE_TYPE_FILE = 'file';
    public const MESSAGE_TYPE_TEMPLATE = 'template';
    public const MESSAGE_TYPE_FLEX = 'flex';

    // -------------------- core fields --------------------
    protected array $raw;             // raw
    protected string $eventId;
    protected string $eventType = '';
    protected string $sourceType;
    protected string $sourceId;
    protected ?string $userId = null;
    protected ?string $groupId = null;
    protected ?string $roomId = null;
    protected ?array $messageContent = null;
    protected ?string $messageType = '';    //todo: director 1->，replyToken/push
    protected ?string $messageId = null;
    protected ?string $messageText = null;
    protected ?string $botKey = null;
    protected $timestamp = 0;

    protected int $direction = 0;
    protected string $replyToken = '';

    /**
     * @param string $httpBody Webhook POST body
     * @param string|null $botKey
     * @return LineMessage[]
     * @throws JsonException
     */
    public static function fromWebhookRequest(string $httpBody, ?string $botKey = null): array
    {
        $decoded = json_decode($httpBody, true, 512, JSON_THROW_ON_ERROR);
        $events = $decoded['events'] ?? [];
        $result = [];

        foreach ($events as $event) {
            $result[] = static::webhookEvent2Instance($event,$botKey);
        }

        return $result;
    }

    static public function webhookEvent2Instance(array $event, ?string $botKey = null){
        $instance = new self();
        $instance->raw = ['envent'=>$event];
        $instance->eventType = $event['type'] ?? '';
        $instance->replyToken = $event['replyToken'] ?? null;
        $source = $event['source'] ?? [];
        $instance->sourceType = $source['type'];
        switch ($instance->sourceType) {
            case LineMessage::SOURCE_TYPE_USER:
                $instance->sourceId = $source['userId'] ?? null;
                $instance->userId   = $instance->sourceId;
                break;
            case LineMessage::SOURCE_TYPE_GROUP:
                $instance->sourceId = $source['groupId'] ?? null;
                $instance->groupId  = $instance->sourceId;
                break;
            case LineMessage::SOURCE_TYPE_ROOM:
                $instance->sourceId = $source['roomId'] ?? null;
                $instance->roomId   = $instance->sourceId;
                break;
            default:
                $instance->sourceId = '';
        }

        $instance->timestamp = $event['timestamp'];
        $instance->eventId = $event['webhookEventId'];

        if ($instance->eventType === self::EVENT_MESSAGE) {
            $instance->messageContent = $event['message'] ?? null;
            $instance->messageType = $event['message']['type'] ?? null;
            $instance->messageId = $event['message']['id'] ?? null;
            $instance->messageText = $event['message']['text'] ?? null;
        }

        $instance->botKey = $botKey;

        $instance->raw = [
            'event' => $event,
            'botKey' => $botKey,
        ];
        return $instance;
    }

    // -------------------- Getter --------------------

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getEventId(){
        return $this->eventId;
    }

    public function getReplyToken(): ?string
    {
        return $this->replyToken;
    }

    public function getSourceType(): string{
        return $this->sourceType;
    }

    public function getSourceId(){
        return $this->sourceId;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getGroupId(): ?string
    {
        return $this->groupId;
    }

    public function getRoomId(): ?string
    {
        return $this->roomId;
    }

    public function getMessageType(): ?string
    {
        return $this->messageType;
    }

    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    public function getMessageText(): ?string
    {
        return $this->messageText;
    }

    public function getRaw(): array
    {
        return $this->raw ?? [];
    }

    public function getBotKey(): ?string
    {
        return $this->botKey;
    }

    public function getTimestamp(){
        return $this->timestamp;
    }

    /**
     * contents
     * @return array|null 
     */
    public function getMessageContent(): ?array
    {
        return $this->messageContent;
    }

    // -------------------- content details; --------------------

    public function getText(): ?string
    {
        return $this->messageType === self::MESSAGE_TYPE_TEXT
            ? $this->messageContent['text'] ?? null
            : null;
    }

    public function getImage(): ?array
    {
        return $this->messageType === self::MESSAGE_TYPE_IMAGE
            ? $this->messageContent
            : null;
    }

    public function getVideo(): ?array
    {
        return $this->messageType === self::MESSAGE_TYPE_VIDEO
            ? $this->messageContent
            : null;
    }

    public function getAudio(): ?array
    {
        return $this->messageType === self::MESSAGE_TYPE_AUDIO
            ? $this->messageContent
            : null;
    }

    public function getLocation(): ?array
    {
        return $this->messageType === self::MESSAGE_TYPE_LOCATION
            ? $this->messageContent
            : null;
    }

    public function getSticker(): ?array
    {
        return $this->messageType === self::MESSAGE_TYPE_STICKER
            ? $this->messageContent
            : null;
    }

    public function getFile(): ?array
    {
        return $this->messageType === self::MESSAGE_TYPE_FILE
            ? $this->messageContent
            : null;
    }

    public function getTemplate(): ?array
    {
        return $this->messageType === self::MESSAGE_TYPE_TEMPLATE
            ? $this->messageContent
            : null;
    }

    public function getFlex(): ?array
    {
        return $this->messageType === self::MESSAGE_TYPE_FLEX
            ? $this->messageContent
            : null;
    }

    public function getDirection(){
        return $this->direction;
    }

    public function toJob(){
        return $this->raw;
    }

    static public function job2LineMessage($jobData){
        return static::webhookEvent2Instance(
            $jobData['event'] ?? [],
            $jobData['botKey'] ?? null
        );
    }

    public function generateLineReplyMessage():?LineReplyMessage{
        if ($this->direction != 1) {
            throw new \Exception('direction must 1;');
        }

        $lineReply = new LineReplyMessage();

        if (!$this->messageContent || !is_array($this->messageContent)) {
            return $lineReply;
        }

        foreach ($this->messageContent as $msg) {
            $type = $msg['type'] ?? $this->messageType;
            switch ($type) {
                case self::MESSAGE_TYPE_TEXT:
                    $text = $msg['text'] ?? '';
                    $lineReply->appendText($text);
                    break;

                case self::MESSAGE_TYPE_IMAGE:
                    $lineReply->appendImage(
                        $msg['originalContentUrl'] ?? '',
                        $msg['previewImageUrl'] ?? ''
                    );
                    break;

                case self::MESSAGE_TYPE_LOCATION:
                    $lineReply->appendLocation(
                        $msg['title'] ?? '',
                        $msg['address'] ?? '',
                        $msg['latitude'] ?? 0,
                        $msg['longitude'] ?? 0
                    );
                    break;

                case self::MESSAGE_TYPE_STICKER:
                    $lineReply->appendSticker(
                        $msg['packageId'] ?? '',
                        $msg['stickerId'] ?? ''
                    );
                    break;

                default:
                    // others
                    break;
            }
        }

        return $lineReply;
    }


    /**
     * toJobPayload
     */
    public function toJobPayload(): array
    {
        return [
            'direction'     => $this->direction,
            'botKey'        => $this->botKey,
            'sourceType'    => $this->sourceType,
            'sourceId'      => $this->sourceId,
            'userId'        => $this->userId,
            'groupId'       => $this->groupId,
            'roomId'        => $this->roomId,
            'messageType'   => $this->messageType,
            'eventType'     => $this->eventType,
            'messageContent'=> $this->messageContent,
            'eventId'       => $this->eventId,
            'raw'           => $this->raw,
            'replyToken'    => $this->replyToken,
        ];
    }

    /**
     * fromJobPayload
     */
    static public function fromJobPayload(array $payload)
    {
        $instance = new self();
        $instance->direction     = $payload['direction'] ?? 0;
        $instance->botKey        = $payload['botKey'] ?? null;
        $instance->sourceType    = $payload['sourceType'] ?? '';
        $instance->sourceId      = $payload['sourceId'] ?? null;
        $instance->userId        = $payload['userId'] ?? null;
        $instance->groupId       = $payload['groupId'] ?? null;
        $instance->roomId        = $payload['roomId'] ?? null;
        $instance->messageType   = $payload['messageType'] ?? null;
        $instance->eventType     = $payload['eventType'] ?? null;
        $instance->messageContent= $payload['messageContent'] ?? null;
        $instance->eventId       = $payload['eventId'] ?? '';
        $instance->raw           = $payload['raw'] ?? null;
        $instance->replyToken           = $payload['replyToken'] ?? null;

        return $instance;
    }

    public function generateReplyLineMessage(LineReplyMessage $lineReplyMessage){
        $messages = $lineReplyMessage->getMessages();
        $messageArray = [];
        foreach($messages as $message){
            $messageArray[] = (array)$message->jsonSerialize();
        }
        $instance = new self();
        $instance->direction     = 1;
        $instance->botKey        = $this->getBotKey();
        $instance->sourceType    = $this->getSourceType();
        $instance->sourceId      = $this->getSourceId();
        $instance->userId        = $this->getUserId();
        $instance->groupId       = $this->getGroupId();
        $instance->roomId        = $this->getRoomId();
        $instance->messageType   = $this->getMessageType();
        $instance->messageContent= (array)$messageArray;
        $instance->timestamp = (int) (microtime(true) * 1000);
        $instance->eventType     = '';
        $instance->eventId       = '';
        $instance->replyToken = $this->replyToken;

        return $instance;
    }
}