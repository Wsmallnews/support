<?php

namespace Wsmallnews\Support\Tenant\Settings\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\LaravelSettings\Events\SavingSettings;
use Spatie\LaravelSettings\Support\Crypto;

class SavingSettingsAutoCreate
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SavingSettings $event): void
    {
        $settings = $event->settings;           // 赋过最新值的 settings class，值是明文（输入框中的未转换的值）
        $properties = $event->properties;           // 最新的 设置属性键值对 collection, 值是明文（输入框中的未转换的值）
        $originalValues = $event->originalValues;   // 编辑之前的 设置属性键值对 collection, 值是数据库中读取的解码后的明文
        $config = $settings->config;                // config 虽然是 private, 但是 settings class 有 __get 方法

        // 这里是初始化，不判断 isLocked，因为 isLocked 是在后续保存时判断的
        // $notRejectedProperties = $properties
        //     ->reject(fn($payload, string $name) => $config->isLocked($name));
        $notRejectedProperties = $properties;

        $changedProperties = $notRejectedProperties
            ->map(function ($payload, string $name) use ($config) {
                if ($cast = $config->getCast($name)) {
                    $payload = $cast->set($payload);
                }

                if ($config->isEncrypted($name)) {
                    $payload = Crypto::encrypt($payload);
                }

                return $payload;
            });

        // 循环保存配置项（值为 class 中预设的初始值）
        $changedProperties->each(function ($payload, string $name) use ($config) {
            // 跳过已存在的配置项
            if ($config->getRepository()->checkIfPropertyExists(
                $config->getGroup(),
                $name
            )) {
                return;
            }

            // 初始化创建设置
            $config->getRepository()->createProperty(
                $config->getGroup(),
                $name,
                $payload
            );
        });

        // 所有标记为 defaultValueLoaded 的属性，都需要重置，否则后续还是会提示 MissingSettings Exception （加载时标记是否加载的是默认值,也就是数据库中没有记录）
        $config->resetDefaultValueLoadedProperties();
    }
}
