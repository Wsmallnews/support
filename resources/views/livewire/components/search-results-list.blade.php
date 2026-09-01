{{-- 分组搜索结果列表：由 search（下拉浮层）与 search-results（结果页）共用，依赖 $groups、$itemRenderers（key => ['view' =>, 'render' =>]）。
    $stickyGroupHeader：分组标题是否吸附（下拉浮层内部滚动时吸附；结果页随页面滚动，默认不吸附）。

    条目渲染：render 闭包优先，否则渲染 itemView() 兜底好的视图（注册方未声明 view 时即为默认统一模板）；
    视图数据契约：$result（SearchResult，含 ->record 原始模型）、$query 关键词（高亮用 text_highlight() 助手）。
    外层的链接包裹（有 url 渲染 <a>）由 support 统一处理，条目视图/闭包只负责内容区。 --}}

@forelse ($groups as $group => $results)
    <div class="py-1">
        <div @class([
                'sn-tip-text px-4 py-1.5 bg-gray-50 dark:bg-gray-800/60',
                'sticky top-0' => $stickyGroupHeader ?? false,
            ])>
            {{ $group }}
        </div>

        @foreach ($results as $result)
            @php
                $rowClass = 'flex items-center gap-3 px-4 py-2.5 sn-link group';
                $render = ($itemRenderers[$result->key] ?? [])['render'] ?? null;

                $itemHtml = $render instanceof \Closure
                    ? $render($result, (string) ($query ?? ''))
                    : view(($itemRenderers[$result->key] ?? [])['view'], [
                        'result' => $result,
                        'query' => (string) ($query ?? ''),
                    ])->render();
            @endphp

            @if ($result->url)
                <a
                    class="{{ $rowClass }}"
                    {{ \Filament\Support\generate_href_html($result->url) }}
                >
                    {!! $itemHtml !!}
                </a>
            @else
                <div class="{{ $rowClass }}">
                    {!! $itemHtml !!}
                </div>
            @endif
        @endforeach
    </div>
@empty
    <x-sn-support::empty-state
        :contained="false"
        icon="heroicon-m-magnifying-glass"
        icon-color="gray"
        :heading="__('sn-support::search.empty')"
        :description="__('sn-support::search.empty_tip')"
    />
@endforelse
