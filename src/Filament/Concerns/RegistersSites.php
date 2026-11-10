<?php

namespace Wsmallnews\Support\Filament\Concerns;

use Closure;
use Filament\Pages\PageConfiguration;
use Filament\Panel;
use Filament\Resources\ResourceConfiguration;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Features\Site\SiteConfig;
use Wsmallnews\Support\Features\Site\SiteMode;
use Wsmallnews\Support\Features\Site\SiteRegistry;
use Wsmallnews\Support\Exceptions\InvalidSiteException;

/**
 * Registers a plugin's resources and pages once per site.
 *
 * A panel may host one site (the common case) or several independent data
 * spaces (main + campaign). Each site becomes a Site in the registry and every
 * resource/page is registered with the site's scope, so the same class can
 * serve several sites inside one panel.
 */
trait RegistersSites
{
    /** @var array<string, SiteConfig> */
    protected array $siteConfigs = [];

    /** @var array<class-string|string, mixed> */
    protected array $siteResources = [];

    /** @var array<class-string|string, mixed> */
    protected array $sitePages = [];

    /**
     * Declare a site on this panel.
     *
     * Called before register(), so the panel id is filled in later.
     */
    public function site(string $key, ?Closure $configure = null): static
    {
        $site = new SiteConfig($this->getId(), '', $key);

        if ($configure !== null) {
            $configure($site);
        }

        $this->siteConfigs[$key] = $site;

        return $this;
    }

    /**
     * Resources to register for every site. Accepts class names or
     * `[Class::class => ['key' => '...', ...]]` overrides, the same shape the
     * old panel_register config used. Calls append; the same class declared
     * again keeps the later overrides (use a tuple for a second instance).
     *
     * @param  array<int|string, mixed>  $resources
     */
    public function resources(array $resources): static
    {
        $this->siteResources = array_merge($this->siteResources, $resources);

        return $this;
    }

    /**
     * Pages to register for every site (including pages owned by another
     * package, e.g. cms registering category's CategoryPage).
     *
     * @param  array<int|string, mixed>  $pages
     */
    public function pages(array $pages): static
    {
        $this->sitePages = array_merge($this->sitePages, $pages);

        return $this;
    }

    /** @return array<string, SiteConfig> */
    public function getSiteConfigs(): array
    {
        return $this->siteConfigs;
    }

    /** Register the plugin's sites and their resources/pages into the panel. */
    protected function registerSiteComponents(Panel $panel): void
    {
        // Plugins using this trait own frontend sites; panel-introduced
        // components (category, comment) go through IntroducesComponents.
        if ($this->siteConfigs === []) {
            throw new InvalidArgumentException(
                "Plugin [{$this->getId()}] must declare at least one site()."
            );
        }

        $sites = $this->resolveSites($panel);

        $this->registerComponents($panel, $sites, count($sites) > 1);
    }

    /**
     * Build a site-like scope for plugins that register admin components but do
     * not own a frontend (no SiteRegistry entry, no route group).
     *
     * @param  array<string, Site>  $sites
     */
    protected function registerComponents(Panel $panel, array $sites, bool $multiple): void
    {
        $resources = [];
        $pages = [];

        foreach ($sites as $site) {
            $seen = [];
            // Shared defaults are per site (panelDefaults), so entries are
            // rebuilt for each one.
            $resourceEntries = $this->resourceEntries($site);
            $pageEntries = $this->pageEntries($site);

            $this->assertInstanceRequirements($site, $resourceEntries);
            $this->assertInstanceRequirements($site, $pageEntries);

            foreach ($resourceEntries as [$class, $overrides]) {
                $key = $this->configurationKey($class, $site, $overrides, $multiple);
                $this->assertUniqueConfiguration($site, $class, $key, $seen);
                $resources[] = $this->buildSiteConfiguration($class, $key, $overrides, $site, $multiple);
            }

            foreach ($pageEntries as [$class, $overrides]) {
                $key = $this->configurationKey($class, $site, $overrides, $multiple);
                $this->assertUniqueConfiguration($site, $class, $key, $seen);
                $pages[] = $this->buildSiteConfiguration($class, $key, $overrides, $site, $multiple);
            }
        }

        if ($resources !== []) {
            $panel->resources($resources);
        }

        if ($pages !== []) {
            $panel->pages($pages);
        }
    }

    /**
     * A class registered several times on one site needs a distinct key per
     * extra instance, and every extra instance must name its partition.
     *
     * @param  list<array{0: class-string, 1: array<string, mixed>}>  $entries
     */
    protected function assertInstanceRequirements(Site $site, array $entries): void
    {
        $byClass = [];

        foreach ($entries as [$class, $overrides]) {
            $byClass[$class][] = $overrides;
        }

        foreach ($byClass as $class => $instances) {
            if (count($instances) < 2) {
                continue;
            }

            $unnamedKey = array_filter($instances, fn (array $overrides): bool => blank($overrides['key'] ?? null));

            // One base registration may keep its derived key; extras must not.
            if (count($unnamedKey) > 1) {
                throw InvalidSiteException::missingInstanceKey($site->id(), $class);
            }

            $unnamedScope = array_filter(
                $instances,
                fn (array $overrides): bool => blank($this->entrySuffix($class, $overrides)),
            );

            // One base registration may use the site's main scope.
            if (count($unnamedScope) > 1) {
                throw InvalidSiteException::missingInstanceScopeSuffix($site->id(), $class);
            }
        }
    }

    /**
     * One class cannot use the same configuration key twice on a site. Two
     * different classes may share a key: Filament looks configurations up by
     * [class][key], so only the pair has to be unique.
     *
     * @param  array<string, class-string>  $seen
     */
    protected function assertUniqueConfiguration(Site $site, string $class, string $key, array &$seen): void
    {
        $identity = "{$class}|{$key}";

        if (isset($seen[$identity])) {
            throw InvalidSiteException::duplicateConfiguration($site->id(), $class, $key);
        }

        $seen[$identity] = $class;
    }

    /**
     * Resolve the configured sites, filling panel id, mode and scope from the
     * plugin conventions, then register them globally.
     *
     * @return array<string, Site>
     */
    protected function resolveSites(Panel $panel): array
    {
        // Aggregate references are resolved against the plugin's own
        // declarations, so declaration order never matters.
        $byKey = [];
        $pluginId = $this->getId();

        foreach ($this->siteConfigs as $config) {
            $byKey[$config->getKey()] = $config;
        }

        foreach ($this->siteConfigs as $config) {
            $sourceKey = $config->getSourceSiteKey();

            if ($sourceKey === null) {
                continue;
            }

            $source = $byKey[$sourceKey]
                ?? throw InvalidSiteException::invalidSource("{$pluginId}:{$config->getKey()}", "{$pluginId}:{$sourceKey}");

            if ($source->isAggregate()) {
                throw InvalidSiteException::invalidSource("{$pluginId}:{$config->getKey()}", "{$pluginId}:{$sourceKey}");
            }
        }

        $resolved = [];
        $keyed = [];

        // 1. Panel id, mode, scope and validation, without freezing anything yet.
        foreach ($this->siteConfigs as $key => $config) {
            $config->panel($panel->getId());

            $this->applySiteDefaults($config, $panel);
            $this->validateSiteMode($config, $panel);

            $keyed[$key] = $config;
        }

        // 2. Resolve sub-scopes from registered components, then freeze + register.
        // SiteRegistry enforces a globally unique data space for non-aggregate sites.
        foreach ($keyed as $key => $config) {
            $config->scopes($this->collectScopes($config, $keyed));

            $site = $config->toSite();

            app(SiteRegistry::class)->register($site);

            $resolved[$key] = $site;
        }

        return $resolved;
    }

    /**
     * Sub-scope map for one site: every entry that carries a suffix registers
     * "suffix => derived pair". The pair is always derived from the site scope,
     * so a suffix can only ever name one partition.
     *
     * @param  array<string, SiteConfig>  $configs
     * @return array<string, array{scope_type: string, scope_id: int}>
     */
    protected function collectScopes(SiteConfig $config, array $configs): array
    {
        $base = $this->baseScopeOf($config, $configs);
        $scopes = [];

        $entries = [...$this->resourceEntries(), ...$this->pageEntries()];

        foreach ($entries as [$class, $overrides]) {
            $suffix = $this->entrySuffix($class, $overrides);

            if (blank($suffix)) {
                continue;
            }

            $scopes[$suffix] = $this->resolveEntryPair($class, $overrides, $base);
        }

        return $scopes;
    }

    /**
     * Base scope of a site before sub-scope derivation. Aggregate sites inherit
     * the (already defaulted) source config's scope.
     *
     * @param  array<string, SiteConfig>  $configs
     * @return array{scope_type: string, scope_id: int}
     */
    protected function baseScopeOf(SiteConfig $config, array $configs): array
    {
        if ($config->getScopeType() !== null) {
            return ['scope_type' => $config->getScopeType(), 'scope_id' => $config->getScopeId()];
        }

        $source = $configs[$config->getSourceSiteKey()] ?? null;

        if ($source === null || $source->getScopeType() === null) {
            throw InvalidSiteException::invalidSource(
                "{$this->getId()}:{$config->getKey()}",
                "{$this->getId()}:{$config->getSourceSiteKey()}",
            );
        }

        return ['scope_type' => $source->getScopeType(), 'scope_id' => $source->getScopeId()];
    }

    /** Fill mode and scope that the caller did not set explicitly. */
    protected function applySiteDefaults(SiteConfig $site, Panel $panel): void
    {
        if ($site->getMode() === null) {
            $site->mode($panel->hasTenancy() ? SiteMode::Tenant : SiteMode::Platform);
        }

        $mode = $site->getMode();

        if ($mode !== SiteMode::Aggregate && blank($site->getScopeType())) {
            // Scope type convention is the plugin id; additional sites get a
            // stable suffix so two sites never share one data scope. The site
            // prefix stays required — a blank one fails in toSite().
            $scopeType = $this->getId();
            $site->scopeable($site->getKey() === 'main' ? $scopeType : "{$scopeType}-{$site->getKey()}");
        }
    }

    /** A tenant site requires a tenant panel, and vice versa. */
    protected function validateSiteMode(SiteConfig $site, Panel $panel): void
    {
        $tenantMode = $site->getMode() === SiteMode::Tenant;

        if ($tenantMode !== $panel->hasTenancy()) {
            throw new InvalidArgumentException(
                "Site [{$this->getId()}:{$panel->getId()}:{$site->getKey()}] uses [{$site->getMode()->value}] mode "
                . 'but panel [' . $panel->getId() . '] tenancy is ' . ($panel->hasTenancy() ? 'enabled' : 'disabled') . '.'
            );
        }
    }

    /**
     * Configuration key. Explicit keys are kept (site-prefixed when several
     * sites share the panel); otherwise a deterministic key is generated from
     * panel + plugin + site + class, so two plugins registering the same class
     * never collide on "default".
     *
     * @param  class-string  $class
     * @param  array<string, mixed>  $overrides
     */
    protected function configurationKey(string $class, Site $site, array $overrides, bool $multiple): string
    {
        $explicit = $overrides['key'] ?? null;

        if (filled($explicit)) {
            return $multiple ? "{$site->key}-{$explicit}" : $explicit;
        }

        return Str::slug(
            "{$site->panelId}-{$this->getId()}-{$site->key}-" . Str::kebab(class_basename($class))
        );
    }

    /**
     * Keep URLs stable regardless of the generated key: a single site uses the
     * class default slug, several sites nest under the site key.
     *
     * @param  class-string  $class
     * @param  array<string, mixed>  $overrides
     */
    protected function configurationSlug(string $class, Site $site, array $overrides, bool $multiple): string
    {
        $slug = $class::getDefaultSlug();
        $explicit = $overrides['key'] ?? null;

        if (! $multiple) {
            return filled($explicit) ? "{$slug}/{$explicit}" : $slug;
        }

        return filled($explicit) ? "{$slug}/{$site->key}/{$explicit}" : "{$slug}/{$site->key}";
    }

    /**
     * @param  class-string  $class
     * @param  array<string, mixed>  $overrides  snake_case config keys
     */
    protected function buildSiteConfiguration(string $class, string $key, array $overrides, Site $site, bool $multiple): ResourceConfiguration | PageConfiguration
    {
        $configuration = $class::make($key);
        $configuration->slug($this->configurationSlug($class, $site, $overrides, $multiple));

        if (method_exists($configuration, 'siteId')) {
            $configuration->siteId($site->id());
        }

        foreach ($overrides as $name => $value) {
            // Handled as a unit below: key (key + slug), scope suffix/pair, navigation group.
            if (in_array($name, ['key', 'scope_suffix', 'scope_type', 'scope_id', 'navigation_group'], true)) {
                continue;
            }

            if ($name !== 'custom_properties' && ! method_exists($configuration, Str::camel($name))) {
                throw new InvalidArgumentException(
                    "Unknown configuration key [{$name}] for component [{$class}]. "
                    . 'Check panel_register.global_default and the entry overrides.'
                );
            }

            $configuration = $this->applyConfigurationValue($configuration, $name, $value);
        }

        // The pair always follows the site; a component only names its
        // partition through a suffix. Explicit pairs would become a second
        // source of truth and could silently share another site's rows.
        foreach (['scope_type', 'scope_id'] as $name) {
            if (array_key_exists($name, $overrides)) {
                throw new InvalidArgumentException(
                    "Component [{$class}] on site [{$site->id()}] uses '{$name}'. "
                    . 'Resources follow their site scope: declare getScopeSuffix() on the class '
                    . "or pass a 'scope_suffix' override instead."
                );
            }
        }

        if (method_exists($configuration, 'scopeType')) {
            $scope = $this->resolveEntryPair($class, $overrides, $this->effectiveScope($site));

            $configuration->scopeType($scope['scope_type']);
            $configuration->scopeId($scope['scope_id']);
        }

        if (method_exists($configuration, 'moduleId') && blank($configuration->getModuleId())) {
            $configuration->moduleId($this->getId());
        }

        if (method_exists($configuration, 'navigationGroup')) {
            // Three layers, highest first: entry override, site panelDefaults,
            // then the fallback — the site label on a multi-site panel, or the
            // module's global_default on a single-site one. Read raw values
            // instead of getNavigationGroup(): evaluating the lazy translation
            // closure here would poison the translation group.
            $group = $overrides['navigation_group']
                ?? $site->panelDefaults['navigation_group']
                ?? ($multiple
                    ? ($site->label ?? Str::headline($site->key))
                    : $this->defaultNavigationGroup());

            if ($group !== null) {
                $configuration->navigationGroup(
                    $group instanceof Closure
                        ? $group
                        : (is_string($group) && str_contains($group, '::') ? fn () => __($group) : $group)
                );
            }
        }

        return $configuration;
    }

    /** Sub-scope suffix declared for this entry (override or class default). */
    protected function entrySuffix(string $class, array $overrides): ?string
    {
        $suffix = $overrides['scope_suffix']
            ?? (method_exists($class, 'getScopeSuffix') ? $class::getScopeSuffix() : null);

        return filled($suffix) ? (string) $suffix : null;
    }

    /**
     * Resolve one entry's scope pair: a suffix derives a partition from the
     * base scope, otherwise the base scope itself applies. The pair is never
     * taken from the entry, so a component cannot claim another data space.
     *
     * @param  class-string  $class
     * @param  array<string, mixed>  $overrides
     * @param  array{scope_type: string, scope_id: int}  $base
     * @return array{scope_type: string, scope_id: int}
     */
    protected function resolveEntryPair(string $class, array $overrides, array $base): array
    {
        $suffix = $this->entrySuffix($class, $overrides);

        if ($suffix !== null) {
            return ['scope_type' => "{$base['scope_type']}::{$suffix}", 'scope_id' => $base['scope_id']];
        }

        return $base;
    }

    /**
     * Base scope of a frozen site; aggregate sites resolve through their source.
     *
     * @return array{scope_type: string, scope_id: int}
     */
    protected function effectiveScope(Site $site): array
    {
        if ($site->isAggregate()) {
            return app(SiteRegistry::class)->scopeableFor($site);
        }

        return ['scope_type' => (string) $site->scopeType, 'scope_id' => $site->scopeId];
    }

    /** Apply a snake_case config value to a configuration object. */
    protected function applyConfigurationValue(ResourceConfiguration | PageConfiguration $configuration, string $name, mixed $value): ResourceConfiguration | PageConfiguration
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
     * Normalise the list/map form into [class, overrides] tuples.
     *
     * @param  array<int|string, mixed>  $entries
     * @return list<array{0: class-string, 1: array<string, mixed>}>
     */
    protected function normaliseEntries(array $entries): array
    {
        $normalised = [];

        foreach ($entries as $key => $value) {
            if (is_int($key)) {
                // A tuple registers one more instance of a class:
                // [SomeResource::class, ['key' => 'vip']]
                $normalised[] = is_array($value)
                    ? [$value[0], (array) ($value[1] ?? []), true]
                    : [$value, [], false];
            } else {
                $normalised[] = [$key, is_array($value) ? $value : [], false];
            }
        }

        return $normalised;
    }

    /**
     * Entries from the published config (`{pluginId}.panel_register`) are the
     * base list; entries declared on the plugin via resources()/pages() append,
     * or merge into the same class when they carry no explicit key.
     *
     * A class may be registered several times (e.g. one resource under two sub
     * scopes); each extra instance must pass a distinct `key`, which also keeps
     * its overrides separate from the first registration.
     *
     * @return list<array{0: class-string, 1: array<string, mixed>}>
     */
    protected function mergeEntries(array $defaults, array $custom, array $shared = []): array
    {
        $merged = [];
        $instances = 0;

        foreach ([$defaults, $custom] as $entries) {
            foreach ($this->normaliseEntries($entries) as [$class, $overrides, $instance]) {
                // Map entries always merge into the class entry; every tuple is
                // a separate instance, so its identity must never collapse.
                $identity = $instance ? $class . '|#' . $instances++ : $class;

                if (isset($merged[$identity])) {
                    $merged[$identity][1] = array_merge($merged[$identity][1], $overrides);
                } else {
                    $merged[$identity] = [$class, array_merge($shared, $overrides)];
                }
            }
        }

        return array_values($merged);
    }

    /**
     * Shared entry defaults for one site: the module's
     * panel_register.global_default first, then the site's panelDefaults.
     * navigation_group is resolved per site in buildSiteConfiguration because
     * it also competes with the site label, so it never enters this merge.
     *
     * @return array<string, mixed>
     */
    protected function entryDefaults(Site $site): array
    {
        $defaults = (array) config($this->getId() . '.panel_register.global_default', []);
        $siteDefaults = $site->panelDefaults;

        $this->assertShareableDefaults($defaults, "config [{$this->getId()}.panel_register.global_default]");
        $this->assertShareableDefaults($siteDefaults, "site [{$site->id()}] panelDefaults");

        unset($defaults['navigation_group'], $siteDefaults['navigation_group']);

        return array_merge($defaults, $siteDefaults);
    }

    /**
     * Entry identity, slug and scope are decided per registration, so they
     * cannot be declared as shared defaults.
     *
     * @param  array<string, mixed>  $defaults
     */
    protected function assertShareableDefaults(array $defaults, string $source): void
    {
        foreach (array_keys($defaults) as $name) {
            if (in_array($name, ['key', 'slug', 'scope_suffix', 'scope_type', 'scope_id'], true)) {
                throw new InvalidArgumentException(
                    "Shared default [{$name}] in {$source} cannot be declared: "
                    . 'it identifies one registration, not all of them.'
                );
            }
        }
    }

    /** Module-wide navigation group, used by single-site panels. */
    protected function defaultNavigationGroup(): mixed
    {
        return config($this->getId() . '.panel_register.global_default.navigation_group');
    }

    /**
     * @param  Site|null  $site  Null skips shared defaults; identity and scope
     *                           suffix are per-entry, so scope collection does
     *                           not need them.
     * @return list<array{0: class-string, 1: array<string, mixed>}>
     */
    protected function resourceEntries(?Site $site = null): array
    {
        return $this->mergeEntries(
            (array) config($this->getId() . '.panel_register.resources', []),
            $this->siteResources,
            $site === null ? [] : $this->entryDefaults($site),
        );
    }

    /**
     * @param  Site|null  $site  Null skips shared defaults.
     * @return list<array{0: class-string, 1: array<string, mixed>}>
     */
    protected function pageEntries(?Site $site = null): array
    {
        return $this->mergeEntries(
            (array) config($this->getId() . '.panel_register.pages', []),
            $this->sitePages,
            $site === null ? [] : $this->entryDefaults($site),
        );
    }

}
