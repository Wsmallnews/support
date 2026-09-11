@php
    use Filament\Support\Icons\Heroicon;

    // 搜索触发方式由按钮开关决定（type/placeholder/aria-label 等通用属性直接写在 input 上）：
    // - 显示按钮：deferred 绑定（wire:model），按钮/回车调用 search() 显式触发
    //   （Livewire 发请求时自动合并 deferred 待定值，更新先于 action 应用）
    // - 无按钮：live 防抖自动搜索，回车立即刷新（#[Url] 自动同步 ?q=）
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
        {{-- show_search_button 开启时传自定义按钮 HTML（HtmlString），以非 inline suffix 渲染
            （与输入框之间保留竖向分割线）；输入框转为 deferred 绑定，搜索由按钮/回车显式触发 --}}
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
