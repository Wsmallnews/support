<?php

namespace Wsmallnews\Support\Features\Search\Engines;

use Illuminate\Support\Collection;
use Wsmallnews\Support\Features\Search\SearchSource;

interface Engine
{
    /**
     * Run the search for one source.
     *
     * @return Collection<int, Model>
     */
    public function search(SearchSource $source, string $query): Collection;
}
