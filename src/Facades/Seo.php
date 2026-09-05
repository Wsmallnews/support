<?php

namespace Wsmallnews\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Features\Seo\SeoPage;

/**
 * @method static static config(string $module, array|\Closure $config)
 * @method static static init(?string $module = null)
 * @method static ?string getFor()
 * @method static array defaults()
 * @method static SeoPage page()
 * @method static SeoPage title(?string $title)
 * @method static SeoPage description(?string $description)
 * @method static SeoPage image(?string $image)
 * @method static SeoPage type(string $type)
 * @method static SeoPage url(?string $url)
 * @method static SeoPage robots(?string $robots)
 * @method static SeoPage jsonLd(array $schema)
 * @method static SeoPage article(array $extra = [])
 * @method static SeoPage website()
 * @method static HtmlString render()
 * @method static HtmlString renderAnalytics()
 *
 * @see \Wsmallnews\Support\Features\Seo\Seo
 */
class Seo extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Wsmallnews\Support\Features\Seo\Seo::class;
    }
}
