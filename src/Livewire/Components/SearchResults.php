<?php

namespace Wsmallnews\Support\Livewire\Components;

use Illuminate\Support\HtmlString;
use Livewire\Attributes\Url;
use Wsmallnews\Support\Facades\Search as SearchFacade;
use Wsmallnews\Support\Features\Site\Site;
use Wsmallnews\Support\Features\Site\SiteContext;
use Wsmallnews\Support\Livewire\Base;
use Wsmallnews\Support\Livewire\Concerns\CanBeContained;

/**
 * Results page body, kept to the result area so callers own the layout and route. The query
 * stays in sync with ?q=; see the Search section of the README.
 */
class SearchResults extends Base
{
    use CanBeContained;

    /** Query, synced both ways with ?q=. */
    #[Url(as: 'q', except: '')]
    public ?string $query = null;

    /** Bound module id; the caller must set it, an empty or unknown id fails the search. */
    public string $module = '';

    /** Per-source result limit; null uses the source or global config. */
    public ?int $limit = null;

    /** Render the submit button; null falls back to module then global. */
    public ?bool $showButton = null;

    public string $placeholder = '';

    public function mount(): void
    {
        $this->placeholder = $this->placeholder ?: __('sn-support::search.placeholder');
    }

    /** Site the component renders in; fail-closed — embedding requires a site context. */
    public function site(): Site
    {
        return app(SiteContext::class)->requireSite();
    }

    public function render()
    {
        return view('sn-support::livewire.components.search-results', [
            'groups' => SearchFacade::search($this->module, (string) $this->query, $this->site(), $this->limit),
            'debounce' => (string) SearchFacade::resolveConfig($this->module, 'debounce', $this->site(), '300ms'),
            'itemRenderers' => SearchFacade::itemRenderers($this->module, $this->site()),
            'showSearchButton' => $this->isShowButton(),
        ]);
    }

    /**
     * Submit button precedence: component property, module, then global. The results page is
     * always page-shaped, so display does not gate it.
     */
    public function isShowButton(): bool
    {
        if ($this->showButton !== null) {
            return $this->showButton;
        }

        return (bool) SearchFacade::resolveConfig($this->module, 'show_search_button', $this->site(), false);
    }

    /**
     * Explicit search for the button and Enter: Livewire applies deferred updates before
     * actions, so the pending query is already current here.
     */
    public function search(): void {}

    /** Submit button HTML rendered in the wrapper suffix; clicking calls search(). */
    public function getSearchButtonHtml(): HtmlString
    {
        return new HtmlString(view('sn-support::livewire.components.search-button', [
            'type' => 'button',
            'wireClick' => 'search',
        ])->render());
    }
}
