<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Support;

use Wsmallnews\Support\Data\ScopeableContext;

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
     * @return mixed
     */
    public static function getConfig(?string $name = null, mixed $default = null): mixed
    {
        $config = config('sn-support');

        return $name ? (data_get($config, $name) ?? $default) : $config;
    }

    /**
     * Get the tenant model class.
     *
     * @return string|null
     */
    public static function getTenantModel(): ?string
    {
        return self::getConfig('tenant_model');
    }

    /**
     * Check if tenancy is enabled.
     *
     * @return bool
     */
    public static function isTenancyEnabled(): bool
    {
        return self::getTenantModel() !== null;
    }

    /**
     * Get the filesystem disk for file storage.
     *
     * @return string|null
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
     * @return ScopeableContext
     *
     * @throws \Wsmallnews\Support\Exceptions\InvalidScopeException
     */
    public static function getScopeFromConfig(string $configKey): ScopeableContext
    {
        return ScopeableContext::fromConfig($configKey);
    }
}
