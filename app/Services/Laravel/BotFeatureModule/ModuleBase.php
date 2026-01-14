<?php
namespace App\Services\Laravel\BotFeatureModule;

use App\Services\LineBot\ReplyEngine;

/**
 * 该类为玩法，也就是功能组件的核心基类；
 * 详细使用用例，参考DemoModule
 * 
 * todo: 其他的一些特性；例如，module可以引用laravel的页面模块渲染玩法页面；
 */
abstract class ModuleBase implements replyEngineModuleInterface{

    /**
     * Human-readable module name.
     * Used mainly for display or debugging purposes.
     */
    abstract static public function getModuleName(): string;

    /**
     * The following two methods work as a pair.
     * External systems communicate with modules using a unique module tag.
     */

    /**
     * Returns the unique identifier of the module.
     *
     * By default, the fully-qualified class name is used
     * to avoid collisions across modules.
     */
    static public function getModuleTag(): string
    {
        return static::class;
    }

    /**
     * Resolve a module class by its module tag.
     *
     * @param string $tag Fully-qualified class name
     * @return string|null Module class name if exists
     */
    static public function getModuleMainByTag(string $tag): ?string
    {
        if (class_exists($tag)) {
            return $tag;
        }
        return null;
    }

    /**
     * Indicates whether this module supports reply engine handling.
     *
     * Modules that return true must implement the required
     * reply engine related methods.
     */
    static public function _isAllowModuleReply(): bool
    {
        return false;
    }
    static public function loadEventList($botId):?array
    {
        throw new \Exception('Not implemented');
    }
    static public function getBotModuleReplyEngine($botId, $initParams = ''):?ReplyEngine
    {
        throw new \Exception('Not implemented');
    }
}