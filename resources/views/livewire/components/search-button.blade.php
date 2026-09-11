{{-- 搜索框一体化提交按钮：由组件包装为 HtmlString 传入 wrapper 的 suffix 渲染，
    样式见包 CSS 的 .sn-search-submit（满高贴右、与输入框融为一体）。
    $type=button 时配合 $wireClick 直接调用组件方法；$type=submit 用于 form wire:submit 场景 --}}
@php
    /** @var string $type */
    /** @var string|null $wireClick */
@endphp
<button type="{{ $type }}" @if (filled($wireClick)) wire:click="{{ $wireClick }}" @endif class="sn-search-submit">
    {{ __('sn-support::search.search_button') }}
</button>
