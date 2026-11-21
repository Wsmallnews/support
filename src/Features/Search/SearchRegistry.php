<?php

namespace Wsmallnews\Support\Features\Search;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Scout\EngineManager;
use Wsmallnews\Support\Exceptions\InvalidScopeException;
use Wsmallnews\Support\Exceptions\SupportException;
use Wsmallnews\Support\Features\Search\Engines\ConfigurableEngine;
use Wsmallnews\Support\Features\Search\Engines\DatabaseEngine;
use Wsmallnews\Support\Features\Search\Engines\Engine;
use Wsmallnews\Support\Features\Search\Engines\ScoutEngine;
use Wsmallnews\Support\Features\Site\Concerns\AssertsSiteOwnership;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Global search registry: modules register sources and options per site, the
 * Livewire component consumes them. See the Search section of the README.
 *
 * Storage stays keyed by module then site. A site's search space belongs to
 * exactly one module (the plugin that owns the site), so writes keep the module
 * argument (ownership is asserted) while reads take only the site and derive it.
 */
class SearchRegistry
{
    use AssertsSiteOwnership;

    /**
     * Module options per site: module => site id => options.
     *
     * @var Collection<string, Collection<string, array<string, mixed>>>
     */
    protected Collection $configs;

    /**
     * Sources per site: module => site id => sources.
     *
     * @var Collection<string, Collection<string, Collection<int, SearchSource>>>
     */
    protected Collection $sources;

    public function __construct()
    {
        $this->configs = collect();
        $this->sources = collect();
    }

    /** Declare options for one site; merged over the previous declaration. */
    public function config(string $module, array $config, Site $site): static
    {
        $this->assertOwnership($module, $site);

        $sites = $this->configs->get($module, collect());
        $sites->put($site->id(), array_merge($sites->get($site->id(), []), $config));

        $this->configs->put($module, $sites);

        return $this;
    }

    /** Read the site's search options (owning module derived from the site). */
    public function getConfig(Site $site, ?string $key = null, mixed $default = null): mixed
    {
        $config = (array) $this->configs->get($this->siteModule($site), collect())->get($site->id(), []);

        return $key === null ? $config : ($config[$key] ?? $default);
    }

    /** Resolve in order: site declaration, global sn-support.search.*, then $default. */
    public function resolveConfig(Site $site, string $key, mixed $default = null): mixed
    {
        return $this->getConfig($site, $key)
            ?? SupportUtils::getSearchConfig($key, $default);
    }

    public function register(string $module, array | SearchSource $source, Site $site): static
    {
        $this->assertOwnership($module, $site);

        // Prefer passing a SearchSource instance (held as-is); arrays are normalized by the constructor.
        // The site is bound here so the source derives its own data space, never the request context.
        $candidate = ($source instanceof SearchSource ? $source : new SearchSource($source))->forSite($site);

        if (blank($candidate->options()['model'] ?? null)) {
            throw new SupportException(__('sn-support::search.exceptions.model_required', ['module' => $module]));
        }

        $this->assertScopeSuffix($candidate, $site);

        $sites = $this->sources->get($module, collect());
        $sources = $sites->get($site->id(), collect());

        // Re-registering the same key overrides, which lets apps replace built-in sources
        $existing = $sources->search(fn (SearchSource $item) => $item->key() === $candidate->key());

        $existing !== false
            ? $sources->put($existing, $candidate)
            : $sources->push($candidate);

        $sites->put($site->id(), $sources);
        $this->sources->put($module, $sites);

        return $this;
    }

    /**
     * @param  array<int, array|SearchSource>  $sources
     */
    public function registers(string $module, array $sources, Site $site): static
    {
        foreach ($sources as $source) {
            $this->register($module, $source, $site);
        }

        return $this;
    }

    /**
     * Run the search and return results grouped by label; every registered source is searched.
     *
     * @return Collection<string, Collection<int, SearchResult>>
     */
    public function search(string $query, Site $site, ?int $limit = null): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        if (! $this->hasSearch($site)) {
            throw new SupportException(__('sn-support::search.exceptions.search_not_found', ['module' => $this->siteModule($site)]));
        }

        $engine = $this->resolveEngine($site);

        $candidates = $this->getSources($site)
            ->filter(fn (SearchSource $source) => $source->visible())
            ->sortBy(fn (SearchSource $source) => $source->sort())
            ->values();

        $groups = collect();

        foreach ($candidates as $source) {
            // Limit precedence: explicit argument > source > site declaration > global
            if ($limit !== null) {
                $source = $source->merge(['limit' => $limit]);
            } elseif (($source->options()['limit'] ?? null) === null) {
                $source = $source->merge(['limit' => $this->resolveConfig($site, 'limit')]);
            }

            $results = $this->searchSource($source, $engine, $query);

            if ($results->isNotEmpty()) {
                $groups->put($source->group(), $groups->get($source->group(), collect())->concat($results)->values());
            }
        }

        return $groups;
    }

    /**
     * @return Collection<int, SearchSource>
     */
    public function getSources(Site $site): Collection
    {
        return $this->sources->get($this->siteModule($site), collect())->get($site->id(), collect());
    }

    /**
     * Item renderers per source key.
     *
     * @return array<string, array{view: string, render: ?Closure}>
     */
    public function itemRenderers(Site $site): array
    {
        return $this->getSources($site)
            ->filter(fn (SearchSource $source) => $source->visible())
            ->mapWithKeys(fn (SearchSource $source): array => [
                $source->key() => ['view' => $source->itemView(), 'render' => $source->itemRender()],
            ])
            ->toArray();
    }

    /** Resolve the results page URL; a closure receives the query, a string gets ?q= appended. */
    public function resolvePage(Site $site, ?string $query = null): ?string
    {
        $page = $this->resolveConfig($site, 'page');

        if ($page instanceof Closure) {
            return $page($query);
        }

        if (blank($page)) {
            return null;
        }

        return filled($query)
            ? $page . (str_contains($page, '?') ? '&' : '?') . 'q=' . urlencode((string) $query)
            : $page;
    }

    /** Whether this site has a registered search (at least one source). */
    protected function hasSearch(Site $site): bool
    {
        return $this->sources->get($this->siteModule($site), collect())->has($site->id());
    }

    /**
     * A source may target one of the site's declared sub-scopes via `scope_suffix`;
     * an unknown suffix fails at registration instead of returning no rows later.
     */
    protected function assertScopeSuffix(SearchSource $source, Site $site): void
    {
        $suffix = $source->options()['scope_suffix'] ?? null;

        if (blank($suffix) || $site->getScope((string) $suffix) !== null) {
            return;
        }

        throw InvalidScopeException::unknownScopeSuffix($site->id(), (string) $suffix, array_keys($site->getScopes()));
    }

    /** Resolve the engine; engines implementing ConfigurableEngine receive the site options. */
    protected function resolveEngine(Site $site): Engine
    {
        $engine = $this->resolveConfig($site, 'engine', 'database');

        if ($engine === 'database') {
            $instance = app(DatabaseEngine::class);
        } elseif ($engine === 'scout') {
            if (! class_exists(EngineManager::class)) {
                throw new SupportException(__('sn-support::search.exceptions.scout_missing'));
            }

            $instance = app(ScoutEngine::class);
        } elseif (is_subclass_of($engine, Engine::class)) {
            $instance = app($engine);
        } else {
            throw new SupportException(__('sn-support::search.exceptions.engine_unknown', ['engine' => $engine]));
        }

        if ($instance instanceof ConfigurableEngine) {
            $instance->setSearchConfig($this->getConfig($site) ?? []);
        }

        return $instance;
    }

    /**
     * @return Collection<int, SearchResult>
     */
    protected function searchSource(SearchSource $source, Engine $engine, string $query): Collection
    {
        if ($source->hasCustomResults()) {
            return $source->customResults($query);
        }

        return $engine
            ->search($source, $query)
            ->map(fn (Model $record) => $source->toResult($record))
            ->values();
    }
}
