<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Livewire\Attributes\Locked;
use Wsmallnews\Support\Features\Modules\ModuleRegistry;
use Wsmallnews\Support\Features\Site\SiteContext;

/**
 * Module context for components reused by another module: scope props isolate data, module
 * addresses config, and the owner module (found through ModuleRegistry) is the fallback.
 */
trait HasModuleContext
{
    /** Consuming module id; null means the owning module uses it directly. */
    #[Locked]
    public ?string $module = null;

    /** Owning module id, the config fallback; unregistered packages throw to force registration. */
    public function getOwnerModule(): string
    {
        return ModuleRegistry::moduleOf(static::class)
            ?? throw new \RuntimeException(sprintf(
                'The owner module of [%s] is not registered in ModuleRegistry: please register a Module descriptor in the package ServiceProvider.',
                static::class
            ));
    }

    /** Module id used for config lookups. */
    public function getModule(): string
    {
        return $this->module ?? $this->getOwnerModule();
    }

    /** Whether an external module consumes this component. */
    public function hasConsumerModule(): bool
    {
        return filled($this->module) && $this->module !== $this->getOwnerModule();
    }

    /** Read config: the consuming module's section first, then the owner's. */
    public function moduleConfig(string $path, mixed $default = null): mixed
    {
        $context = app(SiteContext::class);
        $site = $context->site();
        $consumer = (string) $this->module;

        // The consumer module owns the current site: its site options may apply.
        // Otherwise only static options are read — never another site's context.
        if ($this->hasConsumerModule() && $site !== null && $site->pluginId === $consumer) {
            $value = $site->getOption($path);

            if ($value !== null) {
                return $value;
            }
        }

        return config($this->getOwnerModule() . '.options.' . $path, $default);
    }
}
