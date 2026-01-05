<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $bot_id
 * @property string $source_type
 * @property string $user_id
 * @property string $event_type
 * @property string $event_id
 * @property string $platform_message_id
 * @property int $direction
 * @property string $reply_token
 * @property string $message_type
 * @property string $content
 * @property string $raw_payload
 * @property int $is_read
 * @property bigint $timestamp
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MsgModel extends Model
{
    //
    protected $table = 'msg';
    protected $fillable = ['bot_id','user_id','platform_message_id','direction','message_type','content','raw_payload','is_read'];
}
