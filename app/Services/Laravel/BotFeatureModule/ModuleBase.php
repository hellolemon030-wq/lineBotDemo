<?php
namespace App\Services\Laravel\BotFeatureModule;

use App\Services\LineBot\ReplyEngine;
use Illuminate\Support\Facades\Log;
use ReflectionClass;

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

    static public function getModuleDir(){
        $calledClass = static::class;
        $ref = new ReflectionClass($calledClass);
        $filePath = $ref->getFileName(); // 当前类文件
        $dir = dirname($filePath);
        return $dir;
    }
    static public function getModuleNamespace(){
        $calledClass = static::class;
        $ref = new ReflectionClass($calledClass);
        return $ref->getNamespaceName();
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


    static public function _needFilamentSupport(){
        return false;
    }
    
    static public function getFilamentDiscoveryConfig(): array {
        $calledClass = static::class;
        $reflector = new ReflectionClass($calledClass);
        $fileName = $reflector->getFileName();

        // 1. 获取物理路径 (in: 参数需要绝对路径)
        // dirname() 得到当前类的目录
        $absolutePath = dirname($fileName) . DIRECTORY_SEPARATOR . 'Filament'; 

        // 2. 获取根命名空间 (for: 参数)
        // 假设您的项目遵循 PSR-4，并且命名空间与目录结构匹配。
        $namespace = $reflector->getNamespaceName() . '\\Filament';

        // 如果您的 Resource 放在子命名空间或子目录中（例如 BotAccountResource 放在 Resources 目录）
        // 您可能需要进一步调整路径和命名空间以指向 Resource 的根目录。
        // 最简单的方法是假设 ModuleBase 的子类所在目录就是 Resource 根目录：

        return [
            'in' => $absolutePath,
            'for' => $namespace,
        ];
    }

}