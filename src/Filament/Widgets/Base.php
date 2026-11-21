<?php

namespace Wsmallnews\Support\Filament\Widgets;

use Filament\Widgets\Widget;
use Wsmallnews\Support\Filament\Widgets\Concerns\ResolvesSite;

/**
 * Base for Filament widgets that need the site their page was registered for.
 *
 * Widgets have no site context of their own: the rendering page/resource passes
 * `siteId` via Widget::make(['siteId' => ...]). Widgets that already extend
 * another base (e.g. cms navigation over nestedset) use ResolvesSite directly
 * instead of this class.
 */
abstract class Base extends Widget
{
    use ResolvesSite;
}
