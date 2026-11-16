<?php

namespace Wsmallnews\Support\Livewire\Components\Page;

use Wsmallnews\Support\Facades\Seo;
use Wsmallnews\Support\Livewire\Base;
use Wsmallnews\Support\Models\Page as PageModel;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * Site page content component, used by routed pages and the home page. Address it by slug or
 * by injecting a resolved page; content is the bound composition first, then page content.
 */
class Page extends Base
{

    public ?string $slug = null;

    /**
     * Injected page instance, preferred over the slug lookup. Avoid naming it $page, which
     * would collide with the view data.
     */
    public ?PageModel $pageRecord = null;

    public bool $withSeo = true;

    /** Module providing the composition types; when empty only the content channel works. */
    public ?string $module = null;

    public function render()
    {
        $page = $this->pageRecord
            ?? SupportUtils::getPageModel()::query()
                ->published()
                ->siteContext()
                ->slug($this->slug ?? '')
                ->with('content')
                ->first();

        abort_if(! $page, 404);

        if ($this->withSeo) {
            Seo::title($page->title);
        }

        return view('sn-support::livewire.components.page', [
            'page' => $page,
            'rows' => filled($this->module) ? $page->resolveRows($this->module) : [],
        ]);
    }
}
