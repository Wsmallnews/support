<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Livewire\Attributes\Locked;
use Wsmallnews\Support\Features\Modules\ModuleRegistry;

/**
 * 模块上下文：内嵌组件被其他模块复用时的配置寻址。
 *
 * 复用契约三要素：:scope-type/:scope-id（数据隔离）+ :module（配置寻址）+ theme-view（视图覆盖退路）。
 * 消费方传入 module（模块插件 id，如 sn-shop）后，moduleConfig 优先读消费模块的 config，
 * 未声明时回落组件所有方自身的配置节——同一组件 + 同一视图，两个模块可以有不同的展示形态。
 *
 * 组件所有方无需手写归属：getOwnerModule() 默认经 ModuleRegistry 按类名反查
 * （各包 ServiceProvider 已登记模块描述符）。组件内不生成跳转链接——用户区等
 * 页面级内容由调用方以 slot 注入（链接在调用方的模块语境里生成）。
 */
trait HasModuleContext
{
    /**
     * 消费模块 id（null = 组件所有方自身使用）
     */
    #[Locked]
    public ?string $module = null;

    /**
     * 组件所有方模块 id（配置回落基准）：ModuleRegistry 按类名反查，
     * 未登记的包抛异常提示去 ServiceProvider 登记
     */
    public function getOwnerModule(): string
    {
        return ModuleRegistry::moduleOf(static::class)
            ?? throw new \RuntimeException(sprintf(
                'The owner module of [%s] is not registered in ModuleRegistry: please register a Module descriptor in the package ServiceProvider.',
                static::class
            ));
    }

    /**
     * 当前模块 id（配置寻址用）
     */
    public function getModule(): string
    {
        return $this->module ?? $this->getOwnerModule();
    }

    /**
     * 是否有外部消费模块
     */
    public function hasConsumerModule(): bool
    {
        return filled($this->module) && $this->module !== $this->getOwnerModule();
    }

    /**
     * 模块配置读取：消费模块的 config 优先，回落组件所有方自身的配置节
     */
    public function moduleConfig(string $path, mixed $default = null): mixed
    {
        if ($this->hasConsumerModule()) {
            $value = config("{$this->module}.{$path}");

            if ($value !== null) {
                return $value;
            }
        }

        $value = config($this->getOwnerModule() . ".{$path}");

        return $value ?? $default;
    }
}
