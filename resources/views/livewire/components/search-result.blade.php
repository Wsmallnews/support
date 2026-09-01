{{-- 单条搜索结果行项内容（SearchSource::DEFAULT_ITEM_VIEW 兜底模板）：封面 + 标题（命中高亮）/ 描述 + 徽标。
    数据契约与自定义条目视图一致：$result、$query（高亮用 text_highlight() 助手）。 --}}

<div class="w-9 h-9 shrink-0 overflow-hidden rounded-md bg-gray-100 dark:bg-gray-800">
    @if ($result->coverUrl)
        <img class="w-full h-full object-cover" src="{{ files_url($result->coverUrl) }}" alt="{{ $result->title }}" />
    @else
        <div class="sn-image-placeholder">
            <x-filament::icon icon="heroicon-m-document-text" class="size-4" aria-hidden="true" />
        </div>
    @endif
</div>

<div class="flex flex-col grow gap-0.5 min-w-0">
    <span class="sn-content-text sn-hover text-sm sn-truncate">{!! text_highlight($result->title, $query ?? '') !!}</span>

    @if ($result->description)
        <span class="sn-descript-text text-xs sn-truncate">{!! text_highlight($result->description, $query ?? '') !!}</span>
    @endif
</div>

@if ($result->badge)
    <span class="sn-tip-text shrink-0">{{ $result->badge }}</span>
@endif
