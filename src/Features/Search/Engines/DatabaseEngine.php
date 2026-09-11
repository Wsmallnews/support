<?php

namespace Wsmallnews\Support\Features\Search\Engines;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Wsmallnews\Support\Features\Search\SearchSource;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 数据库 LIKE 引擎（默认）：空白拆词（字段间 OR，多词组合方式由 terms_operator
 * 决定：and 所有词都命中 / or 任一词命中），对中文友好、零外部依赖。
 */
class DatabaseEngine implements ConfigurableEngine
{
    /**
     * 模块级搜索配置（Search::config 声明）；读取时未声明的键回退全局配置
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
            // or 模式首个分组仍用 where（首条分组不带连接词），后续 orWhere
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

    /**
     * 读取搜索配置：模块声明优先（null 视为未声明），未声明走全局 sn-support.search.*
     */
    protected function searchConfig(string $key, mixed $default = null): mixed
    {
        return $this->searchConfig[$key] ?? SupportUtils::getSearchConfig($key, $default);
    }
}
