<?php

namespace Wsmallnews\Support\Livewire\Components;

use Filament\Support\Facades\FilamentView;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Facades\Search as SearchFacade;
use Wsmallnews\Support\Livewire\Base;
use Wsmallnews\Support\Livewire\Concerns\HasProperties;

/**
 * Frontend search component. Sources come from SearchRegistry; the component only handles
 * debouncing, grouping and highlighting. See the Search section of the README.
 */
class Search extends Base
{
    use HasProperties;

    public ?string $query = null;

    /** Bound module id; null searches every enabled module. */
    public ?string $module = null;

    /** Per-source result limit; null uses the source or global config. */
    public ?int $limit = null;

    /** Display mode; null falls back to sn-support.search.display. */
    public ?string $display = null;

    /** Render the inline submit button (page mode only); null falls back to module then global. */
    public ?bool $showButton = null;

    public string $placeholder = '';

    public function mount(): void
    {
        $this->placeholder = $this->placeholder ?: __('sn-support::search.placeholder');

        // Resolved into the property at mount so the view can read $display directly; a public
        // property would shadow any same-named view data.
        $this->display = $this->display ?? (string) SearchFacade::resolveConfig($this->module, 'display', 'dropdown');
    }

    public function render()
    {
        $groups = $this->isDropdownDisplay()
            ? SearchFacade::search($this->module, (string) $this->query, $this->limit)
            : collect();

        return view('sn-support::livewire.components.search', [
            'groups' => $groups,
            'debounce' => (string) SearchFacade::resolveConfig($this->module, 'debounce', '300ms'),
            'itemRenderers' => SearchFacade::itemRenderers($this->module),
            'showSearchButton' => $this->isShowButton(),
        ]);
    }

    /** Whether results render in a dropdown. */
    public function isDropdownDisplay(): bool
    {
        return $this->getDisplay() === 'dropdown';
    }

    /** Resolve the display mode: component property, then module, then global. */
    public function getDisplay(): string
    {
        return $this->display
            ?? (string) SearchFacade::resolveConfig($this->module, 'display', 'dropdown');
    }

    /** Whether to render the submit button (page mode only), same precedence as display. */
    public function isShowButton(): bool
    {
        if ($this->getDisplay() !== 'page') {
            return false;
        }

        if ($this->showButton !== null) {
            return $this->showButton;
        }

        return (bool) SearchFacade::resolveConfig($this->module, 'show_search_button', false);
    }

    /** Submit button HTML passed to the wrapper suffix; clicking equals pressing Enter. */
    public function getSearchButtonHtml(): HtmlString
    {
        return new HtmlString(view('sn-support::livewire.components.search-button', [
            'type' => 'button',
            'wireClick' => 'gotoSearchPage',
        ])->render());
    }

    /** Page mode: navigate to the results page with the query resolved into the URL. */
    public function gotoSearchPage(): void
    {
        $term = trim((string) $this->query);

        if ($term === '') {
            return;
        }

        $url = SearchFacade::resolvePage($this->module, $term);

        if (blank($url)) {
            return;
        }

        $this->redirect($url, FilamentView::hasSpaMode());
    }
}
