@php
    use Wsmallnews\Support\Features\Composition\CompositionRenderer;

    // $rows: CompositionRenderer::resolveRows() 输出
    // lg+ 三等分栅格按 layout 占位；lg 以下固定单列，槽内组件按 left → right 顺序堆叠
    // 注意：Tailwind 经 @source 扫描 blade 源码生成工具类，类名必须字面量书写，禁止动态拼接
@endphp

@props(['rows' => []])

<div class="w-full flex flex-col sn-gap">
    @foreach ($rows as $row)
        @php
            $leftClass = match ($row['layout']) {
                CompositionRenderer::LAYOUT_LEFT_NARROW => '@4xl:col-span-1',
                CompositionRenderer::LAYOUT_LEFT_WIDE => '@4xl:col-span-2',
                default => '@4xl:col-span-3',
            };

            $rightClass = match ($row['layout']) {
                CompositionRenderer::LAYOUT_LEFT_NARROW => '@4xl:col-span-2',
                CompositionRenderer::LAYOUT_LEFT_WIDE => '@4xl:col-span-1',
                default => null,
            };
        @endphp

        <div class="w-full flex flex-col @4xl:grid @4xl:grid-cols-3 items-start sn-gap">
            <div @class([
                'w-full min-w-0 flex flex-col sn-gap',
                $leftClass,
            ])>
                @foreach ($row['left'] as $component)
                    <x-sn-support::composition.block :block="$component" :block-key="'row-' . $loop->parent->index . '-l-' . $loop->index" />
                @endforeach
            </div>

            @if ($rightClass)
                <div @class([
                    'w-full min-w-0 flex flex-col sn-gap',
                    $rightClass,
                ])>
                    @foreach ($row['right'] as $component)
                        <x-sn-support::composition.block :block="$component" :block-key="'row-' . $loop->parent->index . '-r-' . $loop->index" />
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>
