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

    /** Register resources into the panel: config file entries plus PanelProvider overrides. */
    public function registerConfigurableResources(Panel $panel): void
    {
        $panel->resources([...$this->buildConfigurations('resources')]);
        $panel->resources([...$this->resourceConfigurations]);
    }

    /** Register pages into the panel: config file entries plus PanelProvider overrides. */
    public function registerConfigurablePages(Panel $panel): void
    {
        $panel->pages([...$this->buildConfigurations('pages')]);
        $panel->pages([...$this->pageConfigurations]);
    }

    /**
    * Apply resource overrides from the PanelProvider.
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
    * Read config entries, merge the shared defaults and build the configuration objects.
    *
    * @return array<string, ResourceConfiguration|PageConfiguration>
    */
    protected function buildConfigurations(string $type): array
    {
        $raw = $this->getPanelRegister($type);

        // Shared defaults
        $globalDefaults = $this->getPanelRegister('global_default') ?? [];

        // Entries
        $entries = [];
        foreach ($raw as $key => $value) {
            if (is_int($key)) {
                $entries[$value] = [];
            } elseif (is_array($value)) {
                $entries[$key] = $value;
            }
        }

        // Merge shared defaults, then build the configuration objects
        $configurations = [];
        foreach ($entries as $class => $overrides) {
            $merged = array_merge($globalDefaults, $overrides);
            $configurations[$class] = $this->buildConfiguration($class, $merged, $type);
        }

        return $configurations;
    }

    /**
    * Build a configuration object from merged config.
    *
    * @param  class-string  $class
    * @param  array<string, mixed>  $config  Keys in snake_case
    * @param  string  $type  resources or pages
    */
    protected function buildConfiguration(string $class, array $config, string $type): ResourceConfiguration | PageConfiguration
    {
        $key = $config['key'] ?? 'default';
        $configObj = $class::make($key);

        // Default the slug so URLs never contain a /default prefix
        if ($key == 'default' && method_exists($class, 'getDefaultSlug')) {
            $configObj->slug($class::getDefaultSlug());
        }

        foreach ($config as $snakeKey => $value) {
            // Resolve closures
            if ($value instanceof Closure) {
                $value = $value();
            }

            // A string containing :: is treated as a translation key
            if (is_string($value) && str_contains($value, '::')) {
                $value = fn () => __($value);
            }

            // Custom properties are handled separately
            if ($snakeKey === 'custom_properties') {
                if (is_array($value)) {
                    $configObj->customProperties($value);
                }

                continue;
            }

            // snake_case to camelCase setter call
            $setter = Str::camel($snakeKey);
            if (method_exists($configObj, $setter)) {
                $configObj->{$setter}($value);
            }
        }

        // Registration implies ownership: the module that registered the entry becomes module_id,
        // which overwrites any manual declaration (and cross-module registration needs none).
        if (method_exists($configObj, 'moduleId') && method_exists($this, 'getId')) {
            $configObj->moduleId($this->getId());
        }

        return $configObj;
    }
}
