<?php

namespace Wsmallnews\Support\Features\Search;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Wsmallnews\Support\Exceptions\SupportException;
use Wsmallnews\Support\Features\Search\Engines\DatabaseEngine;
use Wsmallnews\Support\Features\Search\Engines\Engine;
use Wsmallnews\Support\Features\Search\Engines\ScoutEngine;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 通用全局搜索注册表：各扩展包在 packageBooted() 中注册可搜索来源，
 * 支持多搜索实例（搜索名 = 插件 ID，互不污染），前端经 Livewire 组件消费。
 *
 * 模块引擎通过 engine() 声明（与来源注册顺序无关、可链式调用），声明后模块内
 * 所有来源统一使用（来源不支持单独设置引擎）；未声明引擎的模块查询时走全局
 * 兜底 config('sn-support.search.engine')。模块的启用开关由各扩展包在注册
 * 入口自行判断（未开启则不注册来源，前端也不渲染搜索框）。
 */
class SearchRegistry
{
    /**
     * 模块引擎：[searchName => engine]（未声明的模块走全局兜底）
     *
     * @var Collection<string, string>
     */
    protected Collection $engines;

    /**
     * 已注册的搜索：[searchName => Collection<int, SearchSource>]
     *
     * @var Collection<string, Collection<int, SearchSource>>
     */
    protected Collection $searches;

    public function __construct()
    {
        $this->engines = collect();
        $this->searches = collect();
    }

    /**
     * 设置模块搜索引擎（null 移除声明，恢复全局兜底），可链式调用、与来源注册顺序无关。
     */
    public function engine(string $search, ?string $engine): static
    {
        filled($engine)
            ? $this->engines->put($search, $engine)
            : $this->engines->forget($search);

        return $this;
    }

    public function register(string $search, array|SearchSource $source): static
    {
        $options = $source instanceof SearchSource ? $source->options() : $source;

        if (blank($options['model'] ?? null)) {
            throw new SupportException(__('sn-support::search.exceptions.model_required', ['search' => $search]));
        }

        $candidate = new SearchSource($options);
        $sources = $this->getSources($search);

        // 相同 key 的来源重复注册时视为覆盖（应用可借此覆盖包内置来源）
        $existing = $sources->search(fn (SearchSource $item) => $item->key() === $candidate->key());

        $existing !== false
            ? $sources->put($existing, $candidate)
            : $sources->push($candidate);

        $this->searches->put($search, $sources);

        return $this;
    }

    /**
     * @param  array<int, array|SearchSource>  $sources
     */
    public function registers(string $search, array $sources): static
    {
        foreach ($sources as $source) {
            $this->register($search, $source);
        }

        return $this;
    }

    public function forget(string $search): static
    {
        $this->searches->forget($search);
        $this->engines->forget($search);

        return $this;
    }

    /**
     * 执行搜索，返回分组结果：Collection<string $group, Collection<int, SearchResult>>。
     *
     * $search 为 null 时搜索所有已启用模块的来源合并（union）；
     * 引擎按模块解析（模块声明优先，全局兜底），模块内所有来源统一。
     */
    public function search(string $query, ?string $search = null, ?int $limit = null): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        if ($search !== null && ! $this->searches->has($search)) {
            throw new SupportException(__('sn-support::search.exceptions.search_not_found', ['search' => $search]));
        }

        $modules = ($search === null
            ? $this->searches
            : collect([$search => $this->searches->get($search)]));

        $groups = collect();

        $candidates = $modules
            ->map(function (Collection $sources, string $moduleName) {
                $engine = $this->resolveEngine($moduleName);

                return $sources->map(fn (SearchSource $source) => [$source, $engine]);
            })
            ->flatten(1)
            ->filter(fn (array $candidate) => $candidate[0]->visible())
            ->sortBy(fn (array $candidate) => $candidate[0]->sort())
            ->values();

        foreach ($candidates as [$source, $engine]) {
            if ($limit !== null) {
                $source = $source->merge(['limit' => $limit]);
            }

            $results = $this->searchSource($source, $engine, $query);

            if ($results->isNotEmpty()) {
                $groups->put($source->group(), $groups->get($source->group(), collect())->concat($results)->values());
            }
        }

        return $groups;
    }

    /**
     * 获取模块声明的引擎（null = 走全局兜底）。
     */
    public function getEngine(string $search): ?string
    {
        return $this->engines->get($search);
    }

    /**
     * 获取指定搜索注册的来源。
     *
     * @return Collection<int, SearchSource>
     */
    public function getSources(string $search): Collection
    {
        return $this->searches->get($search, collect());
    }

    /**
     * 解析模块引擎：模块声明优先，全局配置兜底。
     */
    protected function resolveEngine(string $search): Engine
    {
        $engine = $this->getEngine($search) ?? SupportUtils::getSearchConfig('engine', 'database');

        if ($engine === 'database') {
            return app(DatabaseEngine::class);
        }

        if ($engine === 'scout') {
            if (! class_exists(\Laravel\Scout\EngineManager::class)) {
                throw new SupportException(__('sn-support::search.exceptions.scout_missing'));
            }

            return app(ScoutEngine::class);
        }

        if (is_subclass_of($engine, Engine::class)) {
            return app($engine);
        }

        throw new SupportException(__('sn-support::search.exceptions.engine_unknown', ['engine' => $engine]));
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
