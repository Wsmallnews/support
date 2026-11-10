<?php

namespace Wsmallnews\Support\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Wsmallnews\Support\Features\Search\SearchRegistry;
use Wsmallnews\Support\Features\Search\SearchSource;

/**
 * @method static static config(string $module, array $config, Site $site)
 * @method static mixed getConfig(string $module, Site $site, ?string $key = null, mixed $default = null)
 * @method static mixed resolveConfig(string $module, string $key, Site $site, mixed $default = null)
 * @method static ?string resolvePage(string $module, Site $site, ?string $query = null)
 * @method static static register(string $module, array|SearchSource $source, Site $site)
 * @method static static registers(string $module, array $sources, Site $site)
 * @method static Collection<string, Collection<int, \Wsmallnews\Support\Features\Search\SearchResult>> search(string $module, string $query, Site $site, ?int $limit = null)
 * @method static Collection<int, SearchSource> getSources(string $module, Site $site)
 * @method static array itemRenderers(string $module, Site $site)
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
