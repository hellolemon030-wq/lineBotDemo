<?php
namespace App\Services\Laravel;

use App\Services\LineBot\Bot;
use App\Models\BotModel;
use App\Services\LineBot\BotManager;
use App\Services\LineBot\ExternalTokenManagerInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreBotManager implements BotManager,ExternalTokenManagerInterface
{
    /**
     * memory cache;
     * @var array<string, array{bot: Bot, access_token: string|null, expire_at: int}>
     */
    protected array $runtimeCache = [];

    /**
     * @param string $key
     * @return Bot|null
     */
    public function getRuntimeBot(string $key): ?Bot
    {
        if(empty($key)){
            $key = env('LINE_CHANNEL_ID');
        }

        $bot = $this->queryBot($key);
        $bot->setExternalTokenManager($this);
        return $bot;
    }

    protected $resolveCountLimit = 10;
    public function resolveBot(Bot $bot, $status)
    {
        Log::warning('resolve bot token; status: '.$status);
        switch($status){
            case '401':
                $model = BotModel::where('key',$bot->getKey())->first();
                if($model->token_refresh_count > $this->resolveCountLimit || $model->need_manual_update){
                    Log::warning('resolve error; please check;  linebot:mg modify [] [], try update secret ');
                    return false;
                }
                $updated = BotModel::where('key', $bot->getKey())
                    ->where('token_refresh_count', '<', $this->resolveCountLimit)
                    ->update([
                        'token_refresh_count' => DB::raw('token_refresh_count + 1')
                    ]);
                if($updated){
                    $result = $bot->apiGetToken();
                    if($result['access_token']){
                        Log::info('resolve success, detail '. json_encode($result));
                        BotModel::where('key', $bot->getKey())->update([
                            'access_token' => $result['access_token'],
                            'token_expire_at' => time() + ($result['expires_in'] ?? 24*3600),
                            'token_refresh_count' => 0
                        ]);
                        $bot->setAccessToken($result['access_token']);
                        return true;
                    } else {
                        Log::warning('resolve error, detail '. json_encode($result));
                        return false;
                    }
                }
                return false;
                break;
            case '403':
                Log::warning("Bot {$bot->getKey()} returned 403. Secret may be invalid. Manual update required.");
                BotModel::where('key', $bot->getKey())->update(['need_manual_update' => 1]);
                return false;
                break;
            default:
                break;
        }
    }

    /* ----- 其他 CRUD 操作 ----- */
    public function queryBot(?string $key = null): ?Bot
    {
        $model = BotModel::where('key',$key)->first();
        if (!$model) {
            if($key == env('LINE_CHANNEL_ID')){
                $this->addBot(env('LINE_CHANNEL_ID'),env('LINE_CHANNEL_SECRET'));
            }
            $model = BotModel::where('key', $key)->first();
        };
        return new Bot($model->key, $model->secret, $model->access_token);
    }

    public function addBot(string $key, string $secret): Bot
    {
        $bot = new Bot($key,$secret);
        $tokenResult = $bot->apiGetToken();
        if(array_key_exists('access_token',$tokenResult)){
            $model = BotModel::create([
                'key' => $key,
                'secret' => $secret,
                'access_token' => $tokenResult['access_token'],
                'token_expire_at' => time() + ($tokenResult['expires_in'] ?? 24*3600),
            ]);
            return new Bot($model->key, $model->secret, $model->access_token);
        } else {
            throw new \Exception('get token fail ; please check secret ;detail :'.json_encode($tokenResult));
        }
    }

    public function delBotByAccessKey(string $accessKey): bool
    {
        return BotModel::where('key', $accessKey)->delete() > 0;
    }

    public function modifyBot(string $accessKey, string $secret): ?Bot
    {
        $model = BotModel::where('key', $accessKey)->first();
        if (!$model) {
            throw new \Exception('empty bot record;');
        }

        $bot = new Bot($model->key,$secret);
        $tokenResult = $bot->apiGetToken();

        if($tokenResult['access_token']){
            $model->secret = $secret;
            $model->access_token = $tokenResult['access_token'];
            $model->token_refresh_count = 0;
            $model->token_expire_at = time() + ($result['expires_in'] ?? 24*3600);
            $model->save();
            return $this->queryBot($accessKey);
        } else {
            throw new \Exception('get token fail ; please check secret ;');
        }
    }

    public function listBots(): array
    {
        $models = BotModel::all();
        return $models->map(fn($m) => new Bot($m->key, $m->secret, $m->access_token))->toArray();
    }

    public function getDefaultBot(){
        return $this->getRuntimeBot('');
    }
}