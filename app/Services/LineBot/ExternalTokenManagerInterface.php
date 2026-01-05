<?php
namespace App\Services\LineBot;

/**
 * external bot token modifier
 */
interface ExternalTokenManagerInterface{
    public function resolveBot(Bot $bot,$status);
}