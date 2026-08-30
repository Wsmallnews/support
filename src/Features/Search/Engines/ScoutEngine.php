<?php

namespace Wsmallnews\Support\Features\Search\Engines;

use Illuminate\Support\Collection;
use Wsmallnews\Support\Exceptions\SupportException;
use Wsmallnews\Support\Features\Search\SearchSource;

/**
 * Laravel Scout 引擎：复用 Scout 的驱动体系（Meilisearch/Algolia/collection 等）。
 *
 * 要求模型 use Laravel\Scout\Searchable；该 trait 无法条件引入，
 * 包内模型不直接 use（避免硬依赖），项目可通过模型配置替换为 use 了该 trait 的子类。
 */
class ScoutEngine implements Engine
{
    public function search(SearchSource $source, string $query): Collection
    {
        $modelClass = $source->modelClass();

        if (! in_array('Laravel\Scout\Searchable', class_uses_recursive($modelClass))) {
            throw new SupportException(
                __('sn-support::search.exceptions.scout_trait_missing', ['model' => $modelClass])
            );
        }

        $builder = $source->modifyScoutBuilder($modelClass::search($query));

        return $builder->take($source->limit())->get();
    }
}
