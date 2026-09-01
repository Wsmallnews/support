@php
    use Filament\Support\Icons\Heroicon;

    // 结果页内搜索框：输入防抖实时刷新（#[Url] 自动同步 ?q=），回车立即搜索
    $searchInput = new Illuminate\View\ComponentAttributeBag([
        'type' => 'search',
        "wire:model.live.debounce.{$debounce}" => 'query',
        'wire:keydown.enter' => '$refresh',
        'placeholder' => __('sn-support::search.placeholder'),
        'aria-label' => __('sn-support::search.results_title'),
    ]);
@endphp

<div class="w-full flex flex-col gap-6">
    <div class="w-full max-w-2xl mx-auto">
        <x-filament::input.wrapper
            inline-prefix
            :prefix-icon="Heroicon::MagnifyingGlass"
        >
            <x-filament::input :attributes="$searchInput" />
        </x-filament::input.wrapper>
    </div>

    <div class="sn-container w-full">
        @if (trim((string) $query) !== '')
            @include('sn-support::livewire.components.search-results-list', ['stickyGroupHeader' => false])
        @else
            <div class="px-4 py-16 text-center sn-descript-text">
                {{ __('sn-support::search.results_tip') }}
            </div>
        @endif
    </div>
</div>
