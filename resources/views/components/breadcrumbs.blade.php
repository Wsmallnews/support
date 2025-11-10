@php
    use Illuminate\View\ComponentAttributeBag;

    use function Filament\Support\generate_icon_html;
@endphp

@props([
    'breadcrumbs' => [],
])

<nav {{ $attributes->class(['fi-breadcrumbs']) }}>
    <ol class="fi-breadcrumbs-list">
        @foreach ($breadcrumbs as $breadcrumb)
            <li class="fi-breadcrumbs-item">
                @if (! $loop->first)
                    {{
                        generate_icon_html(\Filament\Support\Icons\Heroicon::ChevronRight, alias: \Filament\Support\View\SupportIconAlias::BREADCRUMBS_SEPARATOR, attributes: (new ComponentAttributeBag)->class([
                            'fi-breadcrumbs-item-separator fi-ltr',
                        ]))
                    }}

                    {{
                        generate_icon_html(\Filament\Support\Icons\Heroicon::ChevronLeft, alias: \Filament\Support\View\SupportIconAlias::BREADCRUMBS_SEPARATOR_RTL, attributes: (new ComponentAttributeBag)->class([
                            'fi-breadcrumbs-item-separator fi-rtl',
                        ]))
                    }}
                @endif

                @if ($loop->last || !isset($breadcrumb['url']) || blank($breadcrumb['url']))
                    <span class="fi-breadcrumbs-item-label">
                        {{ $breadcrumb['label'] }}
                    </span>
                @else
                    <a
                        {{ \Filament\Support\generate_href_html($breadcrumb['url']) }}
                        class="fi-breadcrumbs-item-label"
                    >
                        {{ $breadcrumb['label'] }}
                    </a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
