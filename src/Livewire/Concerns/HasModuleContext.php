<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Livewire\Attributes\Locked;
use Wsmallnews\Support\Features\Modules\ModuleRegistry;

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
