<?php

namespace Wsmallnews\Support\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Wsmallnews\Support\Features\Search\SearchRegistry;
use Wsmallnews\Support\Features\Search\SearchSource;

/**
 * @method static static engine(string $search, ?string $engine)
 * @method static ?string getEngine(string $search)
 * @method static static register(string $search, array|SearchSource $source)
 * @method static static registers(string $search, array $sources)
 * @method static static forget(string $search)
 * @method static Collection<string, Collection<int, \Wsmallnews\Support\Features\Search\SearchResult>> search(string $query, ?string $search = null, ?int $limit = null)
 * @method static Collection<int, SearchSource> getSources(string $search)
 *
 * @see SearchRegistry
 */
class Search extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SearchRegistry::class;
    }
}
