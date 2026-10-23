<?php

namespace Wsmallnews\Support\Features\Search\Engines;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Wsmallnews\Support\Features\Search\SearchSource;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Default LIKE engine: splits on whitespace, OR-ing terms across fields and combining
 * them by terms_operator. No external dependency and friendly to CJK text.
 */
class DatabaseEngine implements ConfigurableEngine
{
    /**
     * Module-level search options from Search::config(); missing keys fall back to the global config.
     *
     * @var array<string, mixed>
     */
    protected array $searchConfig = [];

    public function setSearchConfig(array $config): static
    {
        $this->searchConfig = $config;

        return $this;
    }

    public function search(SearchSource $source, string $query): Collection
    {
        $queryBuilder = $source->modifyQuery($source->modelClass()::query());

        $caseSensitive = ! $this->searchConfig('case_insensitive', true);
        $orTerms = $this->searchConfig('terms_operator', 'and') === 'or';

        foreach ($this->splitTerms($query) as $index => $term) {
            // The first group uses where because it carries no connector
            $combine = $orTerms && $index > 0 ? 'orWhere' : 'where';

            $queryBuilder->{$combine}(function (Builder $scope) use ($source, $term, $caseSensitive) {
                foreach ($source->fields() as $field) {
                    $scope->orWhereLike($field, "%{$term}%", caseSensitive: $caseSensitive);
                }
            });
        }

        return $queryBuilder->limit($source->limit())->get();
    }

    /**
     * @return array<int, string>
     */
    protected function splitTerms(string $query): array
    {
        $query = trim($query);

        if (! $this->searchConfig('split_terms', true)) {
            return [$query];
        }

        $terms = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);

        return filled($terms) ? $terms : [$query];
    }

    /** Read an option: module declaration first (null counts as undeclared), then the global config. */
    protected function searchConfig(string $key, mixed $default = null): mixed
    {
        return $this->searchConfig[$key] ?? SupportUtils::getSearchConfig($key, $default);
    }
}
