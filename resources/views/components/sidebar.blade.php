@props([
    'sidebar' => []
])

@php
    use Filament\Support\Facades\FilamentView;
    use function Filament\Support\generate_href_html;

    $spaMode = FilamentView::hasSpaMode();
@endphp

<ul
    @class([
        'sn-container w-full flex flex-col py-4',
    ])
    role="menu"
>
    @foreach ($sidebar as $menu)
        @php
            $isActive = ($menu['is_active'] ?? false);
            $url = $menu['url'] ?? null;
        @endphp

        <li
            class="flex flex-col"
            role="none"
        >
            <a @class([
                    'sn-link sn-descript-text sn-hover',
                    'flex w-full min-h-11 py-2 justify-between items-center px-4 gap-2 group',
                    'focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500',
                    'sn-active' => $isActive,
                ])
                role="menuitem"
                @if ($isActive) aria-current="page" @endif
                {{ $url ? generate_href_html($url, ($menu['target'] ?? false), $menu['spaMode'] ?? $spaMode) : 'href=javascript:;' }}
            >
                <div class="flex items-center gap-1">
                    {{ $menu['label'] }}
                </div>
            </a>
        </li>
    @endforeach
</ul>
