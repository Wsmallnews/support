@php
    use Filament\Support\Icons\Heroicon;

    // Trigger depends on the button switch: with the button, wire:model is deferred and
    // search() runs on click or Enter; without it, live debounce searches as you type.
    $searchInput = $showSearchButton
        ? new Illuminate\View\ComponentAttributeBag([
            'wire:model' => 'query',
            'wire:keydown.enter' => 'search',
        ])
        : new Illuminate\View\ComponentAttributeBag([
            "wire:model.live.debounce.{$debounce}" => 'query',
            'wire:keydown.enter' => '$refresh',
        ]);
@endphp

<div class="w-full flex flex-col gap-6">
    <div class="w-full max-w-2xl mx-auto">
        {{-- When show_search_button is on, pass the button HTML as a non-inline suffix so the
        wrapper keeps its divider, and switch the input to deferred binding. --}}
        <x-filament::input.wrapper
            class="sn-bg"
            inline-prefix
            :prefix-icon="Heroicon::MagnifyingGlass"
            :suffix="$showSearchButton ? $this->getSearchButtonHtml() : null"
            :inline-suffix="! $showSearchButton"
        >
            <x-filament::input
                type="search"
                placeholder="{{ $placeholder }}"
                aria-label="{{ $placeholder }}"
                :attributes="$searchInput"
            />
        </x-filament::input.wrapper>
    </div>

    <div @class([
        'w-full',
        'sn-container' => $contained,
    ])>
        @if (trim((string) $query) !== '')
            @include('sn-support::livewire.components.search-results-list', ['stickyGroupHeader' => false])
        @else
            <x-sn-support::empty :description="__('sn-support::search.results_tip')" :contained="false" />
        @endif
    </div>
</div>
