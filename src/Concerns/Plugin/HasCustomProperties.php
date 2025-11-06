<?php

declare(strict_types=1);

namespace Wsmallnews\Support\Concerns\Plugin;

use BezhanSalleh\PluginEssentials\Concerns\Plugin\HasPluginDefaults;
use Closure;

trait HasCustomProperties
{
    use HasPluginDefaults;

    protected array $customProperties = [];

    public function customProperties(array | Closure | null $customProperties): static
    {
        // 获取插件默认自定义属性
        $defaultProperties = $this->getPluginDefault('customProperties', $this->activeResourceContext ?? null);
        // 合并传入的自定义属性
        $customProperties = array_merge($defaultProperties, $customProperties);

        if (method_exists($this, 'setContextualProperty')) {
            return $this->setContextualProperty('customProperties', $customProperties);
        }

        $this->customProperties = $customProperties;
        $this->markPropertyAsUserSet('customProperties');

        return $this;
    }

    public function getCustomProperties(?string $resourceClass = null): ?array
    {
        return $this->getPropertyWithDefaults('customProperties', $resourceClass);
    }
}
