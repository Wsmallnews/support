<?php

namespace Wsmallnews\Support\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Wsmallnews\Support\Features\Search\SearchRegistry;
use Wsmallnews\Support\Features\Search\SearchSource;

/**
 * Writes keep the module argument (ownership is asserted); reads take only the site,
 * whose plugin id is the module.
 *
 * @method static static config(string $module, array $config, Site $site)
 * @method static static register(string $module, array|SearchSource $source, Site $site)
 * @method static static registers(string $module, array $sources, Site $site)
 * @method static mixed getConfig(Site $site, ?string $key = null, mixed $default = null)
 * @method static mixed resolveConfig(Site $site, string $key, mixed $default = null)
 * @method static Collection<string, Collection<int, \Wsmallnews\Support\Features\Search\SearchResult>> search(string $query, Site $site, ?int $limit = null)
 * @method static Collection<int, SearchSource> getSources(Site $site)
 * @method static array itemRenderers(Site $site)
 * @method static ?string resolvePage(Site $site, ?string $query = null)
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
