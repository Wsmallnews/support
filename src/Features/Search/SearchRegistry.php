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
 * 通用全局搜索注册表：各扩展包在 packageBooted() 中注册可搜索来源，
 * 支持多模块实例（模块名 = 插件 ID，互不污染），前端经 Livewire 组件消费。
 *
 * 模块选项通过 config() 统一声明：sn-support.search.* 的任意键都可作为模块声明键
 * （与来源注册顺序无关、可链式调用），未声明的键查询时走全局兜底
 * config('sn-support.search.*')，后续 support 新增配置键无需各包同步接线即天然可覆盖。
 * 扩展包推荐整节透传自己的配置节接入：collect(Utils::getConfig('search'))->except('enabled')
 * （enabled 是各包自己的启用门控，不透传）。模块的启用开关由各扩展包在注册入口自行判断
 * （未开启则不注册来源，前端也不渲染搜索框）。
 *
 * 已消费的选项（键名与 sn-support.search.* 一致）：
 * - engine：模块搜索引擎（模块内所有来源统一）；'database' | 'scout' | 引擎类名
 * - display / debounce / show_search_button：前端搜索组件展示方式、防抖、一体化搜索按钮
 * - page：搜索结果页地址（display = page 时搜索框回车跳转目标）。支持 URL 字符串
 *   （support 统一拼接 ?q=关键词）或匿名函数（接收搜索关键词，自行返回完整 URL）
 * - results_limit：来源默认返回条数（来源显式声明 limit 时优先）
 * - split_terms / terms_operator / case_insensitive：拆词开关、多词组合方式（'and' | 'or'）、
 *   LIKE 大小写（database 引擎经 ConfigurableEngine 消费）
 *
 * 读取约定：模块声明统一经 getConfig 读取（null = 未声明）；「模块 ?? 全局」的统一解析
 * 入口是 resolveConfig（resolveEngine / resolvePage / 前端组件兜底均走它）。
 */
class SearchRegistry
{
    /**
     * 模块选项：[searchName => array]（未设置的键走全局兜底）
     *
     * @var Collection<string, array<string, mixed>>
     */
    protected Collection $configs;

    /**
     * 已注册的搜索：[moduleName => Collection<int, SearchSource>]
     *
     * @var Collection<string, Collection<int, SearchSource>>
     */
    protected Collection $modules;

    public function __construct()
    {
        $this->configs = collect();
        $this->modules = collect();
    }

    /**
     * 声明模块选项（增量合并，同名键后声明覆盖；值为 null 的键恢复全局兜底），可链式调用。
     */
    public function config(string $module, array $config): static
    {
        $this->configs->put($module, array_merge($this->configs->get($module, []), $config));

        return $this;
    }

    /**
     * 读取模块选项：$key 为 null 时返回整个选项数组。
     */
    public function getConfig(string $module, ?string $key = null, mixed $default = null): mixed
    {
        $config = $this->configs->get($module, []);

        return $key === null ? $config : ($config[$key] ?? $default);
    }

    /**
     * 「模块声明 > 全局配置 > 默认值」的统一解析入口：模块未声明（或声明 null）走
     * sn-support.search.*，均未声明返回 $default。$module 为 null 时只走全局。
     */
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

        // 相同 key 的来源重复注册时视为覆盖（应用可借此覆盖包内置来源）
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
     * 执行搜索，返回分组结果：Collection<string $group, Collection<int, SearchResult>>。
     *
     * $module 为 null 时搜索所有已启用模块的来源合并（union）；
     * 引擎按模块解析（模块声明优先，全局兜底），模块内所有来源统一。
     * 来源条数：$limit 显式覆盖 > 来源声明 limit > 模块 results_limit > 全局 results_limit。
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
            // 条数优先级：调用方显式 limit > 来源声明 limit > 模块 results_limit 声明 > 全局 results_limit
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
     * 获取指定搜索注册的来源。
     *
     * @return Collection<int, SearchSource>
     */
    public function getSources(string $module): Collection
    {
        return $this->modules->get($module, collect());
    }

    /**
     * 获取来源的条目渲染配置：[key => ['view' => string, 'render' => ?Closure]]。
     *
     * view 经 SearchSource::itemView() 兜底（未声明自定义视图时为默认统一模板）；
     * $module 为 null 时合并所有模块（相同 key 后注册的覆盖）；条目渲染 render 闭包优先。
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

    /**
     * 解析模块搜索结果页地址为最终跳转 URL：模块声明优先，全局配置兜底；$module 为 null 时仅走全局兜底。
     *
     * $query 为回车跳转携带的搜索关键词：闭包声明接收关键词并自行返回完整 URL（含 ?q= 等
     * 参数）；字符串声明由 support 统一拼接 ?q=关键词；$query 为 null 时返回不带关键词的地址。
     */
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

    /**
     * 解析模块引擎：模块声明优先，全局配置兜底。
     * 实现了 ConfigurableEngine 的引擎注入模块声明的选项（terms_operator 等，
     * 未声明的键由引擎自行回退全局配置）。
     */
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
