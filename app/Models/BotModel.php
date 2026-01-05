<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
/**
 * @property int $id
 * @property string $key
 * @property string $secret
 * @property string $access_token
 * @property string $token_refresh_count
 * 
 */
class BotModel extends Model
{
    //
    protected $fillable = ['key', 'secret', 'access_token'];
}
