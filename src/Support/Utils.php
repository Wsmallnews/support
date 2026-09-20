<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Support;

use Wsmallnews\Support\Data\ScopeableContext;
use Wsmallnews\Support\Exceptions\InvalidScopeException;
use Wsmallnews\Support\Exceptions\SupportException;

/**
 * Utility class for support package configuration.
 */
class Utils
{
    /**
     * Get configuration value.
     *
     * @param  string|null  $name  Configuration key (dot notation)
     * @param  mixed  $default  Default value if not found
     */
    public static function getConfig(?string $name = null, mixed $default = null): mixed
    {
        $config = config('sn-support');

        return $name ? (data_get($config, $name) ?? $default) : $config;
    }

    /**
     * Get model class by name.
     *
     * @param  string  $name  Model name (e.g., 'post', 'navigation')
     * @param  bool  $shouldException  Whether to throw exception if not found
     *
     * @throws SupportException
     */
    public static function getModel(string $name, bool $shouldException = true): ?string
    {
        $model = self::getConfig('models')[$name] ?? null;

        if (blank($model) && $shouldException) {
            throw new SupportException("Model {$name} not found.");
        }

        return $model;
    }

    /**
     * Get sms log model class.
     *
     * @return string Models\SmsLog
     */
    public static function getSmsLogModel(): string
    {
        return self::getModel('sms_log');
    }

    /**
     * Get content model class.
     *
     * @return string Models\Content
     */
    public static function getContentModel(): string
    {
        return self::getModel('content');
    }

    /**
     * Get scheduled task model class.
     *
     * @return string Models\ScheduledTask
     */
    public static function getScheduledTaskModel(): string
    {
        return self::getModel('scheduled_task');
    }

    /**
     * Get composition model class.
     *
     * @return string Models\Composition
     */
    public static function getCompositionModel(): string
    {
        return self::getModel('composition');
    }

    /**
     * Get page model class.
     *
     * @return string Models\Page
     */
    public static function getPageModel(): string
    {
        return self::getModel('page');
    }

    /**
     * Get scheduler configuration value.
     *
     * @param  string|null  $name  Configuration key under scheduler (dot notation)
     * @param  mixed  $default  Default value if not found
     */
    public static function getSchedulerConfig(?string $name = null, mixed $default = null): mixed
    {
        return self::getConfig('scheduler' . ($name ? '.' . $name : ''), $default);
    }

    /**
     * Get search configuration (dot notation under "search").
     *
     * @param  string|null  $name  Configuration key under search (dot notation)
     * @param  mixed  $default  Default value if not found
     */
    public static function getSearchConfig(?string $name = null, mixed $default = null): mixed
    {
        return self::getConfig('search' . ($name ? '.' . $name : ''), $default);
    }

    /**
     * Get sitemap configuration (dot notation under "sitemap").
     *
     * @param  string|null  $name  Configuration key under sitemap (dot notation)
     * @param  mixed  $default  Default value if not found
     */
    public static function getSitemapConfig(?string $name = null, mixed $default = null): mixed
    {
        return self::getConfig('sitemap' . ($name ? '.' . $name : ''), $default);
    }

    /**
     * Get feeds configuration (dot notation under "feeds").
     *
     * @param  string|null  $name  Configuration key under feeds (dot notation)
     * @param  mixed  $default  Default value if not found
     */
    public static function getFeedsConfig(?string $name = null, mixed $default = null): mixed
    {
        return self::getConfig('feeds' . ($name ? '.' . $name : ''), $default);
    }

    /**
     * Get theme configuration (dot notation under "theme").
     *
     * @param  string|null  $name  Configuration key under theme (dot notation)
     * @param  mixed  $default  Default value if not found
     */
    public static function getThemeConfig(?string $name = null, mixed $default = null): mixed
    {
        return self::getConfig('theme' . ($name ? '.' . $name : ''), $default);
    }

    /**
     * Get the tenant model class.
     */
    public static function getTenantModel(): ?string
    {
        return self::getConfig('tenant_model');
    }

    /**
     * Check if tenancy is enabled.
     */
    public static function isTenancyEnabled(): bool
    {
        return self::getTenantModel() !== null;
    }

    /**
     * Get the filesystem disk for file storage.
     */
    public static function getFilesystemDisk(): ?string
    {
        return self::getConfig('filesystem_disk') ?: config('filament.default_filesystem_disk');
    }

    /**
     * Get scope context from a module's scopeables instance configuration.
     *
     * 配置形态（module 即模块标识/插件 id，如 sn-cms）：
     *   "{$module}.scopeables" => [
     *       'main'   => ['scope_type' => 'sn-cms', 'scope_id' => 0],   // 默认实例，必须存在
     *       'footer' => ['scope_type' => 'sn-cms-footer', 'scope_id' => 0],   // 差异实例，按需声明
     *   ]
     *
     * 未引用的实例声明合法（预留）；引用不存在的实例键、缺失 main、
     * 或多个实例指向同一分区（scope_type + scope_id 重复）都属配置错误，直接抛异常。
     *
     * @param  string  $configKey  实例配置键（如 'sn-cms.scopeables'）
     * @param  string|null  $key  实例键（null = main 默认实例）
     *
     * @throws InvalidScopeException
     */
    public static function getScopeFromInstances(string $configKey, ?string $key = null): ScopeableContext
    {
        $key ??= 'main';
        $instances = config($configKey);

        if (! is_array($instances) || $instances === []) {
            throw InvalidScopeException::configNotFound($configKey);
        }

        if (! array_key_exists('main', $instances)) {
            throw InvalidScopeException::invalidConfiguration($configKey, 'instance [main] is required');
        }

        if (! array_key_exists($key, $instances)) {
            throw InvalidScopeException::unknownInstance($configKey, $key);
        }

        $fingerprints = [];
        foreach ($instances as $instanceKey => $instance) {
            $instanceConfigKey = "{$configKey}.{$instanceKey}";

            if (! is_array($instance)) {
                throw InvalidScopeException::invalidConfiguration($instanceConfigKey, 'instance must be an array with scope_type and scope_id');
            }

            if (blank($instance['scope_type'] ?? null)) {
                throw InvalidScopeException::missingType($instanceConfigKey);
            }

            if (! isset($instance['scope_id'])) {
                throw InvalidScopeException::missingId($instanceConfigKey);
            }

            $fingerprint = $instance['scope_type'] . ':' . $instance['scope_id'];
            if (isset($fingerprints[$fingerprint])) {
                throw InvalidScopeException::invalidConfiguration(
                    $configKey,
                    "instances [{$fingerprints[$fingerprint]}] and [{$instanceKey}] share the same partition ({$fingerprint})"
                );
            }
            $fingerprints[$fingerprint] = $instanceKey;
        }

        return ScopeableContext::fromArray($instances[$key]);
    }
}
