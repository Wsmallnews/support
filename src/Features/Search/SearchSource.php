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
 * One searchable source: model plus registration options, with defaults resolved.
 * For the option list see the Search section of the README.
 */
class SearchSource
{
    /** Fallback item view when the source declares none. */
    public const DEFAULT_ITEM_VIEW = 'sn-support::livewire.components.search-result';

    public function __construct(protected array $options)
    {
        // Normalise the model to a class name; morph aliases are supported
        if (isset($this->options['model'])) {
            $this->options['model'] = FilamentModelHelper::getModelClassName($this->options['model']);
        }
    }

    public function options(): array
    {
        return $this->options;
    }

    /** Return a new instance with the overrides merged in. */
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
     * LIKE fields: model defaults minus dotted relation paths, which are not queried by default.
     *
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
     * Fully custom results, bypassing the engine.
     *
     * @return Collection<SearchResult>
     */
    public function customResults(string $query): Collection
    {
        return collect(($this->options['results'])($query));
    }

    /** LIKE engine: apply the scopeable filter and the query modifier. */
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

    /** Scout engine: apply the scout modifier. */
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

    /** Links come only from the registrant's url closure, so a frontend search never yields a panel URL. */
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

    /** Declared item view, or the default template. */
    public function itemView(): string
    {
        $view = $this->options['view'] ?? null;

        return filled($view) ? (string) $view : static::DEFAULT_ITEM_VIEW;
    }

    /** Custom render closure fn ($result, $query), which wins over the view. */
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
