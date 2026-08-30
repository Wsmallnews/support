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
     * Get scope context from configuration.
     * This is a helper method for packages that store scope in their config.
     *
     * @param  string  $configKey  Full config key (e.g., 'sn-cms.scopeable')
     *
     * @throws InvalidScopeException
     */
    public static function getScopeFromConfig(string $configKey): ScopeableContext
    {
        return ScopeableContext::fromConfig($configKey);
    }
}
