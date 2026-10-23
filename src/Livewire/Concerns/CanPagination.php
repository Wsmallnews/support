<?php

namespace Wsmallnews\Support\Livewire\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\WithPagination;

trait CanPagination
{
    use WithPagination;

    /** Pagination mode. */
    public string $pageType = 'scroll';      // scroll, paginator or manual

    /** Page parameter name. */
    public string $pageName = 'page';

    /** Items per page. */
    public int $perPage = 10;

    /** Last loaded page; 0 forces a fresh start that replaces instead of merging. */
    public int $loadedPage = 0;

    /** Pagination metadata. */
    public array $pageInfo = [];

    /** Fingerprint of the last query; with $loadedPage it forms the cache key. */
    public ?string $loadedFingerprint = null;

    /**
    * Pagination links.
    *
    * @var string
    */
    protected $links = null;

    public function pageType(string $pageType)
    {
        $this->pageType = $pageType;

        return $this;
    }

    public function getPageType(): string
    {
        return $this->pageType;
    }

    /** Reset the pagination cache so the next render re-queries from page one. */
    public function resetPagination(): void
    {
        $this->loadedPage = 0;
        $this->resetPage($this->pageName);
    }

    /**
    * Run a paginated query.
    *
    * @param  Builder  $builder  Eloquent query
    * @param  string  $fingerprint  Identifies the query conditions; a change resets pagination,
    *                               so it must cover everything that affects the result set
    */
    public function withPagination(Builder $builder, string $fingerprint)
    {
        // Fingerprint changed: full reset (clear cache, back to page one)
        if ($fingerprint !== $this->loadedFingerprint) {
            $this->resetPagination();
            $this->loadedFingerprint = $fingerprint;
        }

        $requestedPage = $this->getPage($this->pageName);

        // Cache hit: same page, same fingerprint, already loaded
        if ($requestedPage === $this->loadedPage && $this->loadedPage > 0) {
            return $this->getCurrents();
        }

        // Fresh start replaces instead of merging
        $isFreshStart = ($this->loadedPage === 0);

        if ($this->getPageType() == 'paginator') {
            /** @var LengthAwarePaginator $current */
            $current = $builder->paginate($this->perPage, pageName: $this->pageName);
            $collections = $current->getCollection();        // Underlying collection
        } else {
            /** @var Paginator $current */
            $current = $builder->simplePaginate($this->perPage, pageName: $this->pageName);
            $collections = $isFreshStart
                ? collect($current->items())
                : $this->getCurrents()->merge($current->items());
        }

        // Remember the page
        $this->loadedPage = $requestedPage;

        // Pagination links
        $this->links = $current->links();

        // Pagination metadata
        $this->pageInfo = [
            'count' => $current->count(),                                       // Total results of the current query
            'per_page' => $current->perPage(),                                  // Page size
            'current_page' => $current->currentPage(),                          // Current page
            'load_status' => 'loading',                                         // Loading state
            'is_last_page' => 0,                                                // Not the last page by default
        ];

        if ($this->getPageType() == 'paginator') {
            $this->pageInfo['total'] = $current->total();                  // Total matching rows
            $this->pageInfo['last_page'] = $current->lastPage();           // Last page number

            if ($this->pageInfo['current_page'] >= $this->pageInfo['last_page']) {
                $this->pageInfo['is_last_page'] = 1;
                $this->pageInfo['load_status'] = 'nomore';

                if ($this->pageInfo['current_page'] == 1 && $this->pageInfo['count'] <= 0) {
                    $this->pageInfo['load_status'] = 'empty';
                }
            }
        } else {
            if ($this->pageInfo['count'] < $this->pageInfo['per_page']) {
                $this->pageInfo['is_last_page'] = 1;
                $this->pageInfo['load_status'] = 'nomore';

                if ($this->pageInfo['current_page'] == 1 && $this->pageInfo['count'] <= 0) {
                    $this->pageInfo['load_status'] = 'empty';
                }
            }
        }

        return $collections;
    }
}
