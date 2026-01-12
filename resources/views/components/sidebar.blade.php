@props([
    'sidebar' => []
])

<ul
    @class([
        'w-full flex flex-col',
    ])
    role="menu"
>
    @foreach ($sidebar as $item)
        @php
            $isActive = ($item['active'] ?? false);
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
                {{ $item['url'] ?? 'href=javascript:;' }}
            >
                <div class="flex items-center gap-1">
                    {{ $item['label'] }}
                </div>
            </a>
        </li>
    @endforeach
</ul>
