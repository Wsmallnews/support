<?php

namespace Wsmallnews\Support\Features\Search\Engines;

use Illuminate\Support\Collection;
use Wsmallnews\Support\Exceptions\SupportException;
use Wsmallnews\Support\Features\Search\SearchSource;

/**
 * Laravel Scout engine, reusing whichever Scout driver is configured. Models must use
 * Searchable, which cannot be applied conditionally, so swap the model via config instead.
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
