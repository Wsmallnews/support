<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Support;

use Wsmallnews\Support\Exceptions\SupportException;

class Utils
{
    public static function getConfig($name = null, $default = null)
    {
        $config = config('sn-support');

        return $name ? (data_get($config, $name) ?? $default) : $config;
    }


    /**
     * 获取 租户模型
     */
    public static function getTenantModel(): ?string
    {
        return self::getConfig('tenant_model') ?? null;
    }

    /**
     * 是否启用了租户
     */
    public static function isTenancyEnabled(): bool
    {
        return self::getTenantModel() !== null;
    }


    /**
     * 获取 文件系统磁盘
     */
    public static function getFilesystemDisk(): ?string
    {
        return self::getConfig('filesystem_disk', null) ?: config('filament.default_filesystem_disk');
    }
}
