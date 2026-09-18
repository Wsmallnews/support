@props([
    'page',
    'rows' => [],
])

{{-- 站点页面内容三分支（编排优先）：绑定编排渲染编排行；未绑定时渲染页面自有 content；绑定失效或无内容渲染空态。
    被 support 的 page 内容组件与 cms 首页共用 --}}
<div class="w-full flex flex-col sn-gap">
    @if (filled($rows))
        <x-sn-support::composition.rows :rows="$rows" />
    @elseif (blank($page->composition_id) && $page->content?->content)
        <div class="sn-container sn-padded">
            <x-sn-support::content
                :contentType="$page->content->content_type"
                :content="$page->content->content"
            />
        </div>
    @else
        <x-sn-support::empty
            :icon="\Filament\Support\Icons\Heroicon::OutlinedDocumentText"
            icon-color="gray"
            :heading="$page->title"
            :description="__('sn-support::page.frontend.empty')"
        />
    @endif
</div>
