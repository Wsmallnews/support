@php
    use Wsmallnews\Support\Features\Composition\CompositionRenderer;

    // $rows: output of CompositionRenderer::resolveRows().
    // Three-column grid from lg up, single stacked column below (left before right).
    // Tailwind scans this file, so every class name must be a literal.
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
