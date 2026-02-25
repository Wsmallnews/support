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
            role="menuitem"
        >
            <a @class([
                    'flex w-full h-10 justify-between items-center px-4 gap-2 group',
                    'sn-link sn-descript-text sn-hover',
                    'sn-active' => $isActive,
                ])
                {{ $url ? generate_href_html($url, ($menu['target'] ?? false), $menu['spaMode'] ?? $spaMode) : 'href=javascript:;' }}
            >
                <div class="flex items-center gap-1">
                    {{ $menu['label'] }}
                </div>
            </a>
        </li>
    @endforeach
</ul>
