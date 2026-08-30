<?php

namespace Wsmallnews\Support\Features\Search\Engines;

use Illuminate\Support\Collection;
use Wsmallnews\Support\Features\Search\SearchSource;

interface Engine
{
    /**
     * 对单个来源执行搜索。
     *
     * @return Collection<int, Model>
     */
    public function search(SearchSource $source, string $query): Collection;
}
