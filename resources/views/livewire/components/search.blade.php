@php
    use Filament\Support\Icons\Heroicon;

    // Blade 组件标签的属性名不支持插值，防抖时长由配置决定，故用属性包传入。
    // dropdown：输入实时防抖搜索；page：回车才搜索并跳转结果页
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
    {{-- page 模式默认以 wrapper 的 suffix 常驻显示 ↵ Enter 提示（参考 Filament 全局搜索，inline-suffix），
        show_search_button 开启时改传自定义按钮 HTML（HtmlString）并以非 inline suffix 渲染：
        与输入框之间保留 wrapper 的竖向分割线，按钮占满 suffix 区（样式见 .sn-search-submit） --}}
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
