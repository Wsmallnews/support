@php
    use function Filament\Support\generate_href_html;
@endphp

{{-- Grouped result list shared by the dropdown and the results page; expects $groups and
$itemRenderers. $stickyGroupHeader sticks group headers while the dropdown scrolls.
A render closure wins over the resolved item view. support wraps the link; the item only
renders its content. View data: $result and $query. --}}

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
                    {{ generate_href_html($result->url) }}
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
    <x-sn-support::empty
        :contained="false"
        icon="heroicon-m-magnifying-glass"
        icon-color="gray"
        :heading="__('sn-support::search.empty')"
        :description="__('sn-support::search.empty_tip')"
    />
@endforelse
