<?php
namespace App\Services\LineBot;

use App\Services\LineBot\Bot;

interface BotManager
{
    /**
     * query Bot
     *
     * @param string|null $key 
     * @return Bot|null
     */
    public function queryBot(?string $key = null): ?Bot;

    /**
     * add Bot
     *
     * @param string $key
     * @param string $secret
     * @return Bot
     */
    public function addBot(string $key, string $secret): Bot;

    /**
     * del Bot
     *
     * @param string $accessKey
     * @return bool delete result 
     */
    public function delBotByAccessKey(string $accessKey): bool;

    /**
     * modify Bot 
     *
     * @param string $accessKey
     * @param string $secret
     * @return Bot|null 
     */
    public function modifyBot(string $accessKey, string $secret): ?Bot;

    /**
     * @return Bot[]
     */
    public function listBots(): array;
}