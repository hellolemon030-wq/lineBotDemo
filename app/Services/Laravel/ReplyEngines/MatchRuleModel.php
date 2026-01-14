<?php
namespace App\Services\Laravel\ReplyEngines;

use Illuminate\Database\Eloquent\Model;

class MatchRuleModel extends Model{
    protected $table = 'match_rule';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'bot_id',
        'keywords',
        'match_type',
        'reply_type',
        'reply_content',
        'module_name',
        'module_call_params',
    ];
}