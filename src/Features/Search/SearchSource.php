<?php

namespace Wsmallnews\Support\Features\Search;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Wsmallnews\Support\Helpers\FilamentModelHelper;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 单个可搜索来源：模型 + 注册项配置的封装，负责默认值解析与展示映射。
 *
 * 注册项支持的选项：
 * - key: 来源标识（默认 morph 别名/类名）
 * - model: 模型类名或 morph 别名（必填）
 * - group: 分组标签（默认模型 label）
 * - fields: LIKE 搜索字段（默认 resolveKeywordSearchFields() 剔除含 '.' 的关联字段）
 * - limit: 返回条数（默认 config('sn-support.search.results_limit')）
 * - sort: 来源排序（越小越靠前）
 * - query: LIKE 引擎的查询修饰闭包（状态/业务过滤）
 * - scout: Scout 引擎的索引过滤闭包（此时 query/scopeable/fields 不生效）
 * - scopeable: LIKE 引擎的 scope 过滤 ['scope_type' => ..., 'scope_id' => ...]
 * - title / description / cover / badge / url: 展示映射闭包（url 仅由注册方提供，默认无链接）
 * - view: 自定义条目视图（接收 $result（含 ->record 原始模型）、$query 关键词；高亮用 text_highlight() 助手）；
 *   未声明时 itemView() 兜底返回 DEFAULT_ITEM_VIEW 默认统一模板
 * - render: 自定义条目渲染闭包 fn ($result, $query) => HtmlString|string（优先于 view）
 * - visible: 来源是否参与本次搜索的闭包
 * - results: 完全自定义结果闭包（绕过引擎，返回 SearchResult 集合）
 */
class SearchSource
{
    /**
     * 默认条目视图（注册方未声明 view 时的兜底）
     */
    public const DEFAULT_ITEM_VIEW = 'sn-support::livewire.components.search-result';

    public function __construct(protected array $options)
    {
        // model 归一化为类名（支持 morph 别名）
        if (isset($this->options['model'])) {
            $this->options['model'] = FilamentModelHelper::getModelClassName($this->options['model']);
        }
    }

    public function options(): array
    {
        return $this->options;
    }

    /**
     * 合并覆盖项（如组件的 limit 覆盖），返回新实例。
     */
    public function merge(array $overrides): static
    {
        return new static(array_merge($this->options, $overrides));
    }

    public function modelClass(): string
    {
        return $this->options['model'];
    }

    public function key(): string
    {
        if ($key = $this->options['key'] ?? null) {
            return (string) $key;
        }

        $alias = array_search($this->options['model'], Relation::morphMap() ?? [], true);

        return $alias !== false ? $alias : class_basename($this->options['model']);
    }

    public function group(): string
    {
        if ($group = $this->options['group'] ?? null) {
            return (string) $group;
        }

        return FilamentModelHelper::getModelLabel($this->options['model']);
    }

    /**
     * LIKE 搜索字段：默认取模型搜索字段，并剔除含 '.' 的关联路径字段（默认不做关联查询）。
     *
     * @return array<int, string>
     */
    public function fields(): array
    {
        if ($fields = $this->options['fields'] ?? null) {
            return array_values((array) $fields);
        }

        $fields = FilamentModelHelper::resolveKeywordSearchFields($this->options['model']);

        return array_values(array_filter($fields, fn (string $field) => ! str_contains($field, '.')));
    }

    public function limit(): int
    {
        return (int) ($this->options['limit'] ?? SupportUtils::getSearchConfig('results_limit', 8));
    }

    public function sort(): int
    {
        return (int) ($this->options['sort'] ?? 0);
    }

    public function visible(): bool
    {
        $visible = $this->options['visible'] ?? null;

        return $visible instanceof Closure ? (bool) $visible() : (bool) ($visible ?? true);
    }

    public function hasCustomResults(): bool
    {
        return ($this->options['results'] ?? null) instanceof Closure;
    }

    /**
     * 完全自定义结果（绕过引擎）。
     *
     * @return Collection<SearchResult>
     */
    public function customResults(string $query): Collection
    {
        return collect(($this->options['results'])($query));
    }

    /**
     * LIKE 引擎：应用 scopeable 过滤与 query 修饰闭包。
     */
    public function modifyQuery(Builder $query): Builder
    {
        $scopeable = $this->options['scopeable'] ?? null;

        if ($scopeable && method_exists($this->options['model'], 'scopeScopeable')) {
            $query->scopeable($scopeable['scope_type'], $scopeable['scope_id']);
        }

        if (($modifier = $this->options['query'] ?? null) instanceof Closure) {
            $query = $modifier($query) ?? $query;
        }

        return $query;
    }

    /**
     * Scout 引擎：应用 scout 修饰闭包。
     */
    public function modifyScoutBuilder(mixed $builder): mixed
    {
        if (($modifier = $this->options['scout'] ?? null) instanceof Closure) {
            $builder = $modifier($builder) ?? $builder;
        }

        return $builder;
    }

    public function title(Model $record): ?string
    {
        return $this->resolveDisplay('title', $record, FilamentModelHelper::getTitle($record));
    }

    public function description(Model $record): ?string
    {
        return $this->resolveDisplay('description', $record, FilamentModelHelper::getDescription($record));
    }

    public function coverUrl(Model $record): ?string
    {
        return $this->resolveDisplay('cover', $record, FilamentModelHelper::getCoverUrl($record));
    }

    /**
     * 跳转链接仅由注册方 url 闭包提供，默认无链接（前端搜索永不产生 panel 地址）。
     */
    public function url(Model $record): ?string
    {
        return $this->resolveDisplay('url', $record);
    }

    public function badge(Model $record): ?string
    {
        return $this->resolveDisplay('badge', $record);
    }

    public function toResult(Model $record): SearchResult
    {
        return new SearchResult(
            key: $this->key(),
            group: $this->group(),
            title: (string) $this->title($record),
            description: $this->description($record),
            coverUrl: $this->coverUrl($record),
            url: $this->url($record),
            badge: $this->badge($record),
            morphType: $record->getMorphClass(),
            record: $record,
        );
    }

    /**
     * 条目视图：注册方声明优先，未声明兜底默认统一模板。
     */
    public function itemView(): string
    {
        $view = $this->options['view'] ?? null;

        return filled($view) ? (string) $view : static::DEFAULT_ITEM_VIEW;
    }

    /**
     * 自定义条目渲染闭包 fn ($result, $query)（优先于视图，null = 未声明）。
     */
    public function itemRender(): ?Closure
    {
        $render = $this->options['render'] ?? null;

        return $render instanceof Closure ? $render : null;
    }

    protected function resolveDisplay(string $key, Model $record, mixed $fallback = null): ?string
    {
        $value = $this->options[$key] ?? null;
        $value = $value instanceof Closure ? $value($record) : $value;
        $value ??= $fallback;

        return filled($value) ? (string) $value : null;
    }
}
