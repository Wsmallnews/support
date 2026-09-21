<?php

namespace Wsmallnews\Support\Features\Modules;

use Filament\Contracts\Plugin;
use RuntimeException;

/**
 * 模块登记表：模块身份的单一事实源。
 *
 * 各包 ServiceProvider 在 packageRegistered() 里登记一次（描述符对象），换取：
 * - 类反查：ModuleRegistry::moduleOf($class) 按命名空间最长前缀匹配得到模块 id，
 *   HasModuleContext::getOwnerModule() 的默认实现据此免去每个组件手写归属；
 * - 存在性校验：各功能注册表（SidebarMenuRegistry/UserConfig/Search/Feed）登记时
 *   可校验 module id 已登记，防止拼错 id 静默失效；
 * - 元信息访问：get()/all()/plugin()。
 *
 * 纯静态（boot 期写入、请求期只读；测试用 flush 重置），无需容器绑定。
 */
class ModuleRegistry
{
    /**
     * @var array<string, Module> 模块 id => 模块描述符
     */
    protected static array $modules = [];

    /**
     * 命名空间根排序缓存（moduleOf 最长前缀匹配用）
     *
     * @var array<string, string>|null namespace => module id
     */
    protected static ?array $namespaceIndex = null;

    /**
     * 登记模块（重复登记同 id 覆盖，boot 幂等）
     */
    public static function register(Module $module): void
    {
        self::$modules[$module->id] = $module;
        self::$namespaceIndex = null;
    }

    /**
     * 模块是否已登记
     */
    public static function has(string $id): bool
    {
        return isset(self::$modules[$id]);
    }

    /**
     * 取模块描述符
     */
    public static function get(string $id): ?Module
    {
        return self::$modules[$id] ?? null;
    }

    /**
     * 全部已登记模块
     *
     * @return array<string, Module>
     */
    public static function all(): array
    {
        return self::$modules;
    }

    /**
     * 类（或对象）所属模块 id：按命名空间最长前缀匹配
     */
    public static function moduleOf(string | object $class): ?string
    {
        $class = is_object($class) ? $class::class : $class;

        if (blank(self::$namespaceIndex)) {
            self::$namespaceIndex = [];

            foreach (self::$modules as $module) {
                self::$namespaceIndex[rtrim($module->namespace, '\\')] = $module->id;
            }

            // 长命名空间优先（Wsmallnews\Cms\Livewire 优先于 Wsmallnews\Cms）
            uksort(self::$namespaceIndex, fn (string $a, string $b) => strlen($b) <=> strlen($a));
        }

        foreach (self::$namespaceIndex as $namespace => $id) {
            if ($class === $namespace || str_starts_with($class, $namespace . '\\')) {
                return $id;
            }
        }

        return null;
    }

    /**
     * 模块的 Filament 插件实例（未登记或无插件的模块返回 null）
     */
    public static function plugin(string $id): ?Plugin
    {
        $plugin = self::get($id)?->plugin;

        return filled($plugin) && class_exists($plugin) ? app($plugin) : null;
    }

    /**
     * 校验模块已登记，未登记抛异常（各功能注册表的入口防御）
     */
    public static function require(string $id): Module
    {
        return self::get($id) ?? throw new RuntimeException(
            "Module [{$id}] is not registered in ModuleRegistry: please register a Module descriptor in the package ServiceProvider's packageRegistered() method."
        );
    }

    /**
     * 重置登记表（测试用）
     */
    public static function flush(): void
    {
        self::$modules = [];
        self::$namespaceIndex = null;
    }
}
