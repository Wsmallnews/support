@php
    use Filament\Support\Icons\Heroicon;
    use Filament\Support\View\SupportIconAlias;
    use Illuminate\View\ComponentAttributeBag;

    use function Filament\Support\generate_href_html;
    use function Filament\Support\generate_icon_html;
@endphp

@props([
    'breadcrumbs' => [],
])

<nav {{ $attributes->class(['fi-breadcrumbs'])->merge(['aria-label' => __('sn-support::support.breadcrumb_nav')]) }}>
    <ol class="fi-breadcrumbs-list">
        @foreach ($breadcrumbs as $breadcrumb)
            <li class="fi-breadcrumbs-item" @if ($loop->last) aria-current="page" @endif>
                @if (! $loop->first)
                    {{
                        generate_icon_html(Heroicon::ChevronRight, alias: SupportIconAlias::BREADCRUMBS_SEPARATOR, attributes: (new ComponentAttributeBag)->class([
                            'fi-breadcrumbs-item-separator fi-ltr',
                        ]))
                    }}

                    {{
                        generate_icon_html(Heroicon::ChevronLeft, alias: SupportIconAlias::BREADCRUMBS_SEPARATOR_RTL, attributes: (new ComponentAttributeBag)->class([
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
                        {{ generate_href_html($breadcrumb['url']) }}
                        class="fi-breadcrumbs-item-label"
                    >
                        {{ $breadcrumb['label'] }}
                    </a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
