<?php

use Illuminate\Support\Facades\Route;
use Wsmallnews\Support\Http\Controllers\SitemapController;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/*
|--------------------------------------------------------------------------
| Support Routes
|--------------------------------------------------------------------------
|
| Only robots.txt stays at the root. sitemap.xml and feed endpoints belong to
| a site and are registered inside that site's route group, so one deployment
| can expose independent endpoints per frontend.
|
*/

// robots.txt is deployment-level: it belongs to no site and no tenant.
Route::middleware(['web'])->group(function (): void {
    if (SupportUtils::getSitemapConfig('enabled', true) === true) {
        Route::get(SupportUtils::getSitemapConfig('robots_uri', 'robots.txt'), [SitemapController::class, 'robots'])->name('sn-support.robots');
    }
});
