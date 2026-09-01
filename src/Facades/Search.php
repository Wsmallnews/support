<?php

namespace Wsmallnews\Support\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Wsmallnews\Support\Features\Search\SearchRegistry;
use Wsmallnews\Support\Features\Search\SearchSource;

/**
 * @method static static config(string $module, array $config)
 * @method static mixed getConfig(string $module, ?string $key = null, mixed $default = null)
 * @method static ?string resolvePage(?string $module, ?string $query = null)
 * @method static static register(string $module, array|SearchSource $source)
 * @method static static registers(string $module, array $sources)
 * @method static static forget(string $module)
 * @method static Collection<string, Collection<int, \Wsmallnews\Support\Features\Search\SearchResult>> search(?string $module, string $query, ?int $limit = null)
 * @method static Collection<int, SearchSource> getSources(string $module)
 * @method static array itemRenderers(?string $module)
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
