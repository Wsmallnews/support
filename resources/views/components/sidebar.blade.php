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
        'w-full flex flex-col',
    ])
    role="menu"
>
    @foreach ($sidebar as $item)
        @php
            $isActive = ($item['active'] ?? false);
            $url = $item['url'] ?? null;
        @endphp

        <li
            class="flex flex-col"
            role="menuitem"
        >
            <a @class([
                    'flex w-full h-10 justify-between items-center px-4 gap-2 rounded-md group hover:text-primary-500 dark:hover:text-primary-600 hover:bg-gray-200 dark:hover:bg-gray-800',
                    'text-gray-700 dark:text-white' => !$isActive,
                    'text-primary-500 dark:text-primary-600' => $isActive,
                ])
                {{ $url ? generate_href_html($url, ($item['target'] ?? false), $item['spaMode'] ?? $spaMode) : 'href=javascript:;' }}
            >
                <div class="flex items-center gap-1">
                    {{ $item['label'] }}
                </div>
            </a>
        </li>
    @endforeach
</ul>
