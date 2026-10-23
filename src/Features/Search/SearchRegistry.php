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
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Global search registry: modules register sources and per-module options, the Livewire
 * component consumes them. See the Search section of the README for the option list.
 */
class SearchRegistry
{
    /**
     * @var Collection<string, array<string, mixed>> module => options (missing keys fall back to global)
     */
    protected Collection $configs;

    /**
     * @var Collection<string, Collection<int, SearchSource>>
     */
    protected Collection $modules;

    public function __construct()
    {
        $this->configs = collect();
        $this->modules = collect();
    }

    /** Declare module options; a later call wins, and null restores the global fallback. */
    public function config(string $module, array $config): static
    {
        $this->configs->put($module, array_merge($this->configs->get($module, []), $config));

        return $this;
    }

    /** Read module options; null key returns the whole array. */
    public function getConfig(string $module, ?string $key = null, mixed $default = null): mixed
    {
        $config = $this->configs->get($module, []);

        return $key === null ? $config : ($config[$key] ?? $default);
    }

    /** Resolve in order: module declaration, global sn-support.search.*, then $default. */
    public function resolveConfig(?string $module, string $key, mixed $default = null): mixed
    {
        return ($module !== null ? $this->getConfig($module, $key) : null)
            ?? SupportUtils::getSearchConfig($key, $default);
    }

    public function register(string $module, array | SearchSource $source): static
    {
        $options = $source instanceof SearchSource ? $source->options() : $source;

        if (blank($options['model'] ?? null)) {
            throw new SupportException(__('sn-support::search.exceptions.model_required', ['module' => $module]));
        }

        $candidate = new SearchSource($options);
        $sources = $this->getSources($module);

        // Re-registering the same key overrides, which lets apps replace built-in sources
        $existing = $sources->search(fn (SearchSource $item) => $item->key() === $candidate->key());

        $existing !== false
            ? $sources->put($existing, $candidate)
            : $sources->push($candidate);

        $this->modules->put($module, $sources);

        return $this;
    }

    /**
     * @param  array<int, array|SearchSource>  $sources
     */
    public function registers(string $module, array $sources): static
    {
        foreach ($sources as $source) {
            $this->register($module, $source);
        }

        return $this;
    }

    public function forget(string $module): static
    {
        $this->modules->forget($module);
        $this->configs->forget($module);

        return $this;
    }

    /**
     * Run the search and return results grouped by label.
     *
     * A null module searches every registered module; the engine is resolved per module.
     *
     * @return Collection<string, Collection<int, SearchResult>>
     */
    public function search(?string $module, string $query, ?int $limit = null): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        if ($module !== null && ! $this->modules->has($module)) {
            throw new SupportException(__('sn-support::search.exceptions.search_not_found', ['module' => $module]));
        }

        $modules = ($module === null
            ? $this->modules
            : collect([$module => $this->modules->get($module)]));

        $groups = collect();

        $candidates = $modules
            ->map(function (Collection $sources, string $moduleName) {
                $engine = $this->resolveEngine($moduleName);

                return $sources->map(fn (SearchSource $source) => [$moduleName, $source, $engine]);
            })
            ->flatten(1)
            ->filter(fn (array $candidate) => $candidate[1]->visible())
            ->sortBy(fn (array $candidate) => $candidate[1]->sort())
            ->values();

        foreach ($candidates as [$moduleName, $source, $engine]) {
            // Limit precedence: explicit argument > source > module results_limit > global
            if ($limit !== null) {
                $source = $source->merge(['limit' => $limit]);
            } elseif (($source->options()['limit'] ?? null) === null) {
                $source = $source->merge(['limit' => $this->resolveConfig($moduleName, 'results_limit')]);
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
    public function getSources(string $module): Collection
    {
        return $this->modules->get($module, collect());
    }

    /**
     * Item renderers per source key; a null module merges every module (later keys win).
     *
     * @return array<string, array{view: string, render: ?Closure}>
     */
    public function itemRenderers(?string $module): array
    {
        $sources = $module !== null
            ? $this->getSources($module)
            : $this->modules->flatten(1);

        return $sources
            ->filter(fn (SearchSource $source) => $source->visible())
            ->mapWithKeys(fn (SearchSource $source): array => [
                $source->key() => ['view' => $source->itemView(), 'render' => $source->itemRender()],
            ])
            ->toArray();
    }

    /** Resolve the results page URL; a closure receives the query, a string gets ?q= appended. */
    public function resolvePage(?string $module, ?string $query = null): ?string
    {
        $page = $this->resolveConfig($module, 'page');

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

    /** Resolve the engine; engines implementing ConfigurableEngine receive the module options. */
    protected function resolveEngine(string $module): Engine
    {
        $engine = $this->resolveConfig($module, 'engine', 'database');

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
            $instance->setSearchConfig($this->configs->get($module, []));
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
