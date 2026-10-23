@props([
    'text',
])

<div class="flex items-center justify-center my-3">
    {{-- Left rule --}}
    <div class="flex-grow h-px bg-gray-200 dark:bg-gray-800"></div>
    {{-- Label --}}
    <span class="mx-2 text-xs font-medium text-gray-400 dark:text-gray-600">{{ $text }}</span>
    {{-- Right rule --}}
    <div class="flex-grow h-px bg-gray-200 dark:bg-gray-800"></div>
</div>