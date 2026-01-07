@props([
    'text',
])

<div class="flex items-center justify-center my-3">
    {{-- 左侧线段 --}}
    <div class="flex-grow h-px bg-gray-200 dark:bg-gray-800"></div>
    {{-- 中间文字 --}}
    <span class="mx-2 text-xs font-medium text-gray-400 dark:text-gray-600">{{ $text }}</span>
    {{-- 右侧线段 --}}
    <div class="flex-grow h-px bg-gray-200 dark:bg-gray-800"></div>
</div>