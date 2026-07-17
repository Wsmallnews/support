<?php

namespace Wsmallnews\Support\Filament\Concerns;

use Closure;
use Filament\Pages\PageConfiguration;
use Filament\Panel;
use Filament\Resources\ResourceConfiguration;
use Illuminate\Support\Str;

trait RegistersConfigurable
{
    /**
     * @var array<ResourceConfiguration>
     */
    protected array $resourceConfigurations = [];

    /**
     * @var array<PageConfiguration>
     */
    protected array $pageConfigurations = [];

    /**
     * 注册 resources 到 Panel（配置文件 + PanelProvider 覆盖）
     */
    public function registerConfigurableResources(Panel $panel): void
    {
        $panel->resources([...$this->buildConfigurations('resources')]);
        $panel->resources([...$this->resourceConfigurations]);
    }

    /**
     * 注册 pages 到 Panel（配置文件 + PanelProvider 覆盖）
     */
    public function registerConfigurablePages(Panel $panel): void
    {
        $panel->pages([...$this->buildConfigurations('pages')]);
        $panel->pages([...$this->pageConfigurations]);
    }


    /**
     * 从 PanelProvider 覆盖资源配置
     *
     * @param  array<ResourceConfiguration>  $configurations
     * @return $this
     */
    public function configurableResources(array $configurations): static
    {
        $this->resourceConfigurations = $configurations;

        return $this;
    }

    /**
     * Get the configurable resources.
     *
     * @return array<ResourceConfiguration>
     */
    public function getConfigurableResources(): array
    {
        return $this->resourceConfigurations;
    }

    /**
     * Accept pre-built PageConfiguration objects from PanelProvider.
     *
     * @param  array<PageConfiguration>  $configurations
     * @return $this
     */
    public function configurablePages(array $configurations): static
    {
        $this->pageConfigurations = $configurations;

        return $this;
    }

    /**
     * Get the configurable pages.
     *
     * @return array<PageConfiguration>
     */
    public function getConfigurablePages(): array
    {
        return $this->pageConfigurations;
    }


    /**
     * 从配置文件读取条目，合并共享默认，构建 Configuration 对象
     *
     * @return array<string, ResourceConfiguration|PageConfiguration>
     */
    protected function buildConfigurations(string $type): array
    {
        $raw = $this->getPanelRegister($type);

        // 提取 global_default 共享默认
        $globalDefaults = $this->getPanelRegister('global_default') ?? [];

        // 分离条目
        $entries = [];
        foreach ($raw as $key => $value) {
            if (is_int($key)) {
                $entries[$value] = [];
            } elseif (is_array($value)) {
                $entries[$key] = $value;
            }
        }

        // 合并共享默认，构建 Configuration 对象
        $configurations = [];
        foreach ($entries as $class => $overrides) {
            $merged = array_merge($globalDefaults, $overrides);
            $configurations[$class] = $this->buildConfiguration($class, $merged, $type);
        }

        return $configurations;
    }

    /**
     * 从合并后的配置数组构建 Configuration 对象
     *
     * @param  class-string  $class
     * @param  array<string, mixed>  $config  键名使用 snake_case
     * @param  string  $type  'resources' | 'pages'
     */
    protected function buildConfiguration(string $class, array $config, string $type): ResourceConfiguration | PageConfiguration
    {
        $key = $config['key'] ?? 'default';
        $configObj = $class::make($key);

        // 设置 slug 为资源默认值，避免 URL 出现 /default 前缀
        if ($key == 'default' && method_exists($class, 'getDefaultSlug')) {
            $configObj->slug($class::getDefaultSlug());
        }

        foreach ($config as $snakeKey => $value) {
            // 解析闭包
            if ($value instanceof Closure) {
                $value = $value();
            }

            // 翻译：字符串且包含 :: 视为翻译键
            if (is_string($value) && str_contains($value, '::')) {
                $value = fn () => __($value);
            }

            // 自定义属性单独处理
            if ($snakeKey === 'custom_properties') {
                if (is_array($value)) {
                    $configObj->customProperties($value);
                }
                continue;
            }

            // snake_case → camelCase 转换调用 setter
            $setter = Str::camel($snakeKey);
            if (method_exists($configObj, $setter)) {
                $configObj->{$setter}($value);
            }
        }

        return $configObj;
    }
}
