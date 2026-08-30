<?php

namespace Wsmallnews\Support\Features\Search\Engines;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Wsmallnews\Support\Features\Search\SearchSource;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 数据库 LIKE 引擎（默认）：空白拆词（多词 AND、字段间 OR），对中文友好、零外部依赖。
 */
class DatabaseEngine implements Engine
{
    public function search(SearchSource $source, string $query): Collection
    {
        $queryBuilder = $source->modifyQuery($source->modelClass()::query());

        $caseSensitive = ! SupportUtils::getSearchConfig('case_insensitive', true);

        foreach ($this->splitTerms($query) as $term) {
            $queryBuilder->where(function (Builder $scope) use ($source, $term, $caseSensitive) {
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

        if (! SupportUtils::getSearchConfig('split_terms', true)) {
            return [$query];
        }

        $terms = preg_split('/\s+/u', $query, -1, PREG_SPLIT_NO_EMPTY);

        return filled($terms) ? $terms : [$query];
    }
}
