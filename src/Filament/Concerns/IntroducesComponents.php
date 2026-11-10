<?php

namespace Wsmallnews\Support\Filament\Concerns;

use Closure;
use Filament\Pages\PageConfiguration;
use Filament\Panel;
use Filament\Resources\ResourceConfiguration;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Wsmallnews\Support\Enums\ScopePolicy;

/**
 * Introduces a plugin's resources and pages on a panel owned by another
 * plugin or by the panel itself.
 *
 * Introduced components have no Site — they were not registered through a
 * site-owning plugin, so there is no site scope to derive from. Any component
 * that consumes scope must declare it explicitly at registration:
 *
 *   CommentPlugin::make()->pages([
 *       CommentPage::class => ['scope_suffix' => 'review'],
 *   ])
 *
 * Components that never consume scope (their tables have no scopeable fields)
 * declare themselves scope-free via the plugin's scopePolicy().
 *
 * The published config list (`{pluginId}.panel_register.resources|pages`) is
 * the base declaration source — entries are commented-off by default so the
 * config doubles as a directory of what the package offers; uncommenting an
 * entry registers it. Inline resources()/pages() declarations are merged in
 * and win over the config list for the same class.
 */
trait IntroducesComponents
{
    /** @var array<int|string, mixed> */
    protected array $introducedResources = [];

    /** @var array<int|string, mixed> */
    protected array $introducedPages = [];

    /** Whether introduced components consume scopeable fields. Defaults to Required; only scope-free packages override. */
    public function scopePolicy(): ScopePolicy
    {
        return ScopePolicy::Required;
    }

    /**
     * Components to introduce on the panel. Accepts class names or
     * `[Class::class => ['scopeable' => [...], ...]]` overrides.
     *
     * @param  array<int|string, mixed>  $resources
     */
    public function resources(array $resources): static
    {
        $this->introducedResources = $resources;

        return $this;
    }

    /**
     * @param  array<int|string, mixed>  $pages
     */
    public function pages(array $pages): static
    {
        $this->introducedPages = $pages;

        return $this;
    }

    /** Register the introduced components on the panel. */
    protected function registerIntroducedComponents(Panel $panel): void
    {
        $resources = [];
        $pages = [];

        foreach ($this->mergedEntries('resources') as [$class, $overrides]) {
            $resources[] = $this->buildIntroducedConfiguration($panel, $class, $overrides);
        }

        foreach ($this->mergedEntries('pages') as [$class, $overrides]) {
            $pages[] = $this->buildIntroducedConfiguration($panel, $class, $overrides);
        }

        if ($resources !== []) {
            $panel->resources($resources);
        }

        if ($pages !== []) {
            $panel->pages($pages);
        }
    }

    /**
     * Config list entries (uncommented in the published config) merged with
     * inline declarations; for the same class the inline overrides win.
     *
     * @param  'resources'|'pages'  $type
     * @return list<array{0: class-string, 1: array<string, mixed>}>
     */
    protected function mergedEntries(string $type): array
    {
        $configEntries = (array) config($this->getId() . ".panel_register.{$type}", []);
        $inline = $type === 'pages' ? $this->introducedPages : $this->introducedResources;

        $merged = [];

        foreach ([$configEntries, $inline] as $entries) {
            foreach ($this->normaliseIntroducedEntries($entries) as [$class, $overrides]) {
                $merged[$class] = array_key_exists($class, $merged)
                    ? array_merge($merged[$class], $overrides)
                    : $overrides;
            }
        }

        return array_map(
            fn (array $overrides, string $class): array => [$class, $overrides],
            $merged,
            array_keys($merged)
        );
    }

    /**
     * Keys are derived from panel + plugin + class.
     */
    protected function buildIntroducedConfiguration(Panel $panel, string $class, array $overrides): PageConfiguration | ResourceConfiguration
    {
        $key = Str::slug("{$panel->getId()}-{$this->getId()}-" . Str::kebab(class_basename($class)));

        $configuration = $class::make($key);
        $configuration->slug($class::getDefaultSlug());

        if (blank($configuration->getModuleId())) {
            $configuration->moduleId($this->getId());
        }

        if ($this->scopePolicy() === ScopePolicy::Required) {
            // Explicit pairs are a second source of truth: scope follows the
            // introducing plugin ([pluginId, 0]), a suffix appends "::suffix" —
            // the same rules as RegistersSites.
            if (isset($overrides['scope_type']) || isset($overrides['scope_id'])) {
                throw new InvalidArgumentException(
                    "Component [{$class}] uses explicit 'scope_type'/'scope_id'. Scope follows the "
                    . "introducing plugin ([{$this->getId()}, 0]) by default; pass a 'scope_suffix' override for a sub-scope."
                );
            }

            $scopeType = $this->getId();

            if ($suffix = $overrides['scope_suffix']
                ?? (method_exists($class, 'getScopeSuffix') ? $class::getScopeSuffix() : null)) {
                $scopeType .= '::' . $suffix;
            }

            $configuration->scopeType($scopeType);
            $configuration->scopeId(0);
        }

        foreach ($overrides as $name => $value) {
            // Handled as a unit: key (key + slug), scope (suffix or the injected pair), navigation group.
            if (in_array($name, ['key', 'scope_suffix', 'scope_type', 'scope_id', 'navigation_group'], true)) {
                continue;
            }

            if ($name !== 'custom_properties' && ! method_exists($configuration, Str::camel($name))) {
                throw new InvalidArgumentException(
                    "Unknown configuration key [{$name}] for component [{$class}]."
                );
            }

            $configuration = $this->applyIntroducedValue($configuration, $name, $value);
        }

        if (blank($configuration->getNavigationGroup())) {
            // Two layers, highest first: entry override, then the module's global_default.
            $group = $overrides['navigation_group']
                ?? config($this->getId() . '.panel_register.global_default.navigation_group');

            if ($group !== null) {
                // Resolve translations lazily: registration runs before lang files load.
                $configuration->navigationGroup(str_contains($group, '::') ? fn () => __($group) : $group);
            }
        }

        return $configuration;
    }

    /** Apply a snake_case config value to a configuration object. */
    protected function applyIntroducedValue(PageConfiguration | ResourceConfiguration $configuration, string $name, mixed $value): PageConfiguration | ResourceConfiguration
    {
        if ($value instanceof Closure) {
            $value = $value();
        }

        // A string containing :: is a translation key.
        if (is_string($value) && str_contains($value, '::')) {
            $value = fn () => __($value);
        }

        if ($name === 'custom_properties') {
            if (is_array($value) && method_exists($configuration, 'customProperties')) {
                $configuration->customProperties($value);
            }

            return $configuration;
        }

        $setter = Str::camel($name);

        if (method_exists($configuration, $setter)) {
            $configuration->{$setter}($value);
        }

        return $configuration;
    }

    /**
     * Normalise the list/map form into [class, overrides] pairs.
     *
     * @param  array<int|string, mixed>  $entries
     * @return array<int, array{0: class-string, 1: array<string, mixed>}>
     */
    protected function normaliseIntroducedEntries(array $entries): array
    {
        $normalised = [];

        foreach ($entries as $key => $value) {
            $normalised[] = is_int($key) ? [$value, []] : [$key, is_array($value) ? $value : []];
        }

        foreach ($normalised as [$class, $overrides]) {
            if (! is_string($class)) {
                throw new InvalidArgumentException(
                    "Introduced components must be class-strings or [class => overrides] maps. "
                    . "For full control (module/scope), register the configuration object directly on the panel instead."
                );
            }
        }

        return $normalised;
    }
}
