@php
    use Filament\Support\Icons\Heroicon;

    // Blade attribute names cannot interpolate, so the debounce value ships as an attribute bag.
    // dropdown searches while typing; page searches on Enter and navigates to the results page.
    $searchInput = $display === 'page'
        ? new Illuminate\View\ComponentAttributeBag([
            'wire:model' => 'query',
            'wire:keydown.enter' => 'gotoSearchPage',
        ])
        : new Illuminate\View\ComponentAttributeBag([
            "wire:model.live.debounce.{$debounce}" => 'query',
            '@focus' => 'open = true',
            '@input' => 'open = true',
            '@keydown.escape' => 'open = false',
        ]);
@endphp

<div
    class="w-full relative"
    x-data="{ open: false }"
    @click.outside="open = false"
>
    {{-- In page mode the suffix shows the Enter hint by default; with show_search_button it
    renders the custom button instead, which fills the whole suffix area. --}}
    <x-filament::input.wrapper
        class="sn-bg"
        inline-prefix
        :prefix-icon="Heroicon::MagnifyingGlass"
        :suffix="$showSearchButton ? $this->getSearchButtonHtml() : ($display === 'page' ? '↵ Enter' : null)"
        :inline-suffix="! $showSearchButton"
    >
        <x-filament::input
            type="search"
            placeholder="{{ $placeholder }}"
            aria-label="{{ $placeholder }}"
            :attributes="$searchInput"
        />
    </x-filament::input.wrapper>

    @if ($display === 'dropdown' && trim((string) $query) !== '')
        <div
            class="sn-container absolute z-40 mt-2 w-full max-h-96 overflow-y-auto"
            x-cloak
            x-show="open"
        >
            @include('sn-support::livewire.components.search-results-list', ['stickyGroupHeader' => true])
        </div>
    @endif
</div>
