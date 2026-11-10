<?php

namespace Wsmallnews\Support\Features\Search;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Scout\EngineManager;
use Wsmallnews\Support\Exceptions\SupportException;
use Wsmallnews\Support\Features\Search\Engines\ConfigurableEngine;
use Wsmallnews\Support\Features\Search\Engines\DatabaseEngine;
use Wsmallnews\Support\Features\Search\Engines\Engine;
use Wsmallnews\Support\Features\Search\Engines\ScoutEngine;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Global search registry: modules register sources and per-module options, the Livewire
 * component consumes them. See the Search section of the README for the option list.
 */
class SearchRegistry
{
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

    /** Declare options for one site; merged over the module config for that site. */
    public function config(string $module, array $config, Site $site): static
    {
        $sites = $this->configs->get($module, collect());
        $sites->put($site->id(), array_merge($sites->get($site->id(), []), $config));

        $this->configs->put($module, $sites);

        return $this;
    }

    /** Read module options of one site. */
    public function getConfig(string $module, Site $site, ?string $key = null, mixed $default = null): mixed
    {
        $config = (array) $this->configs->get($module, collect())->get($site->id(), []);

        return $key === null ? $config : ($config[$key] ?? $default);
    }

    /** Resolve in order: module declaration, global sn-support.search.*, then $default. */
    public function resolveConfig(string $module, string $key, Site $site, mixed $default = null): mixed
    {
        return $this->getConfig($module, $site, $key)
            ?? SupportUtils::getSearchConfig($key, $default);
    }

    public function register(string $module, array | SearchSource $source, Site $site): static
    {
        // Prefer passing a SearchSource instance (held as-is); arrays are normalized by the constructor
        $candidate = $source instanceof SearchSource ? $source : new SearchSource($source);

        if (blank($candidate->options()['model'] ?? null)) {
            throw new SupportException(__('sn-support::search.exceptions.model_required', ['module' => $module]));
        }

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
     * Run the search and return results grouped by label; every source of the module is searched.
     *
     * @return Collection<string, Collection<int, SearchResult>>
     */
    public function search(string $module, string $query, Site $site, ?int $limit = null): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        if (! $this->hasSearch($module, $site)) {
            throw new SupportException(__('sn-support::search.exceptions.search_not_found', ['module' => $module]));
        }

        $engine = $this->resolveEngine($module, $site);

        $candidates = $this->getSources($module, $site)
            ->filter(fn (SearchSource $source) => $source->visible())
            ->sortBy(fn (SearchSource $source) => $source->sort())
            ->values();

        $groups = collect();

        foreach ($candidates as $source) {
            // Limit precedence: explicit argument > source > module declaration > global
            if ($limit !== null) {
                $source = $source->merge(['limit' => $limit]);
            } elseif (($source->options()['limit'] ?? null) === null) {
                $source = $source->merge(['limit' => $this->resolveConfig($module, 'limit', $site)]);
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
    public function getSources(string $module, Site $site): Collection
    {
        return $this->sources->get($module, collect())->get($site->id(), collect());
    }

    /**
     * Item renderers per source key.
     *
     * @return array<string, array{view: string, render: ?Closure}>
     */
    public function itemRenderers(string $module, Site $site): array
    {
        return $this->getSources($module, $site)
            ->filter(fn (SearchSource $source) => $source->visible())
            ->mapWithKeys(fn (SearchSource $source): array => [
                $source->key() => ['view' => $source->itemView(), 'render' => $source->itemRender()],
            ])
            ->toArray();
    }

    /** Resolve the results page URL; a closure receives the query, a string gets ?q= appended. */
    public function resolvePage(string $module, Site $site, ?string $query = null): ?string
    {
        $page = $this->resolveConfig($module, 'page', $site);

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

    /** Whether this site has the module's search (at least one registered source). */
    protected function hasSearch(string $module, Site $site): bool
    {
        return $this->sources->get($module, collect())->has($site->id());
    }

    /** Resolve the engine; engines implementing ConfigurableEngine receive the module options. */
    protected function resolveEngine(string $module, Site $site): Engine
    {
        $engine = $this->resolveConfig($module, 'engine', $site, 'database');

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
            $instance->setSearchConfig($this->getConfig($module, $site) ?? []);
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
