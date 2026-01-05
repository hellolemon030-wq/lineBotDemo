<?php
namespace App\Services\LineBot;

use Exception;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\ReplyMessageRequest;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class Bot
{
    protected string $key;
    protected string $secret;
    protected ?string $accessToken;
    protected ?MessagingApiApi $messagingApi = null;

    protected ExternalTokenManagerInterface $externalTokenManager;

    public function __construct(string $key, string $secret, ?string $accessToken = null)
    {
        $this->key         = $key;
        $this->secret      = $secret;
        $this->accessToken = $accessToken;
    }

    // -------------------- Getter --------------------
    public function getKey(): string
    {
        return $this->key;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function setAccessToken(string $token): void
    {
        $this->accessToken = $token;
        $this->messagingApi = null;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setExternalTokenManager(ExternalTokenManagerInterface $externalTokenManager){
        $this->externalTokenManager = $externalTokenManager;
    }

    // -------------------- offical SDK Bot demo --------------------
    /**
     * get MessagingApiApi instance
     *
     * @return MessagingApiApi
     * @throws \RuntimeException
     */
    protected function getMessagingApi(): MessagingApiApi
    {
        if ($this->messagingApi) {
            return $this->messagingApi;
        }

        $config = new \LINE\Clients\MessagingApi\Configuration();
        $config->setAccessToken($this->accessToken);
        
        $this->messagingApi = new MessagingApiApi(new Client(), $config);

        return $this->messagingApi;
    }

    /**
     * validateRequestSignature
     *
     * @param string $signature X-Line-Signature
     * @param string $body 
     * @return bool
     * 
     * //todo: excetion handle;
     */
    public function validateRequestSignature(string $signature, string $body): bool
    {
        $hash = base64_encode(hash_hmac('sha256', $body, $this->secret, true));
        return hash_equals($signature, $hash);
    }

    //todo: excetion handle;
    public function replyByToken(?string $replyToken, LineReplyMessage $lineReplyMessage)
    {
        Log::info('api -> reply by replyToken');
        $this->tryAndRetry(function() use ($replyToken,$lineReplyMessage){
            $messagingApi = $this->getMessagingApi();
            $messages = $lineReplyMessage->getMessages();

            $request = new ReplyMessageRequest([
                'replyToken' => $replyToken,
                'messages'   => $messages,
            ]);
            return $messagingApi->replyMessage($request);
        });
    }

    //todo: excetion handle;
    public function pushUserMessage(string $userId, LineReplyMessage $msg)
    {
        Log::info('api -> pushUserMessage');
        return $this->tryAndRetry(function() use ($userId,$msg){
            $api = $this->getMessagingApi();
            $response = $api->pushMessage([
                'to' => $userId,
                'messages' => $msg->getMessages()
            ]);
            return $response;
        });
    }

    protected function tryAndRetry($callback){
        try {
            return $callback();
        } catch (\LINE\Clients\MessagingApi\ApiException $e) {
            Log::info('retry');
            $status = $e->getCode();
            if ($this->externalTokenManager) {
                $resolveResult = $this->externalTokenManager->resolveBot($this, $status);
                if($resolveResult){
                    // try again;
                    try {
                        return $callback();
                    } catch (\Throwable $t) {
                        return [
                            'status' => 0,
                            'error' => $t->getMessage(),
                            'http_code' => $t instanceof \LINE\Clients\MessagingApi\ApiException ? $t->getCode() : null
                        ];
                    }
                }
                return [
                    'status' => 0,
                    'error' => $e->getMessage(),
                    'http_code' => $status
                ];
            } else {
                throw $e;
            }
        } catch (\Throwable $t) {
            return [
                'status' => 0,
                'error' => $t->getMessage(),
            ];
        }
    }

    // -------------------- get Channel Access Token --------------------
    /**
     *  get Channel Access Token by LINE API 
     * @return array|null
     */
    public function apiGetToken(): ?array
    {
        Log::warning('api -> apiToken request');
        $url = 'https://api.line.me/v2/oauth/accessToken';
        $payload = [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->key,
            'client_secret' => $this->secret,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);

        //{"access_token":"0NQDLaxpK4gqXvTSZL2Ede5U58vCx70Fmaa5J62kXxK97L7WVFq3EPUGy/6cT8WKELPglov4NlFX1RCgLWuuJRmSxGa81LXIaLMEUX/9vJ5Ue9rH5+eQkCaVQF7ICfZpt1toBpEVxtQagHKzbI9RKI9PbdgDzCFqoOLOYbqAITQ=","expires_in":2592000,"token_type":"Bearer"}  

        if ($response === false) {
            throw new Exception('LINE token request failed: ' . curl_error($ch));
            // Log::warning('LINE token request failed: ' . curl_error($ch));
            // curl_close($ch);
            // return null;
        }

        curl_close($ch);

        return json_decode($response, true);

        $data = json_decode($response, true);
        return [
            'access_token' => $data['access_token'],
            'expires_in'   => $data['expires_in']
        ];
    }

    public function checkTokenHealth(){
        $client = new Client([
            'timeout' => 5,
        ]);

        try {
            $response = $client->get(
                'https://api.line.me/v2/bot/info',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->getAccessToken(),
                    ],
                ]
            );

            return [
                'ok' => true,
                'status' => $response->getStatusCode(),
                'data' => json_decode($response->getBody()->getContents(), true),
            ];

        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                return [
                    'ok' => false,
                    'status' => $e->getResponse()->getStatusCode(),
                    'error' => (string) $e->getResponse()->getBody(),
                ];
            }

            return [
                'ok' => false,
                'exception' => $e->getMessage(),
            ];
        }
    }
}