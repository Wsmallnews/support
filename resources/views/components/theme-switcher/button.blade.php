@props([
    'icon',
    'theme',
])

@php
    $label = __("filament-panels::layout.actions.theme_switcher.{$theme}.label");
@endphp

<button
    aria-label="{{ $label }}"
    type="button"
    x-on:click="(theme = @js($theme)) && close()"
    x-tooltip="{
        content: @js($label),
        theme: $store.snSupportFrontendTheme,
    }"
    x-bind:class="{
        'text-primary-500 dark:text-primary-400 bg-gray-100 dark:bg-white/10': theme === @js($theme),
        'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200': theme !== @js($theme)
    }"
    class="sn-support-theme-switcher-btn inline-flex items-center justify-center min-w-11 min-h-11 rounded-md p-2 hover:bg-gray-100 dark:hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-900 transition-colors duration-200 motion-reduce:transition-none cursor-pointer"
>
    {{
        \Filament\Support\generate_icon_html($icon, alias: match ($theme) {
            'light' => \Filament\View\PanelsIconAlias::THEME_SWITCHER_LIGHT_BUTTON,
            'dark' => \Filament\View\PanelsIconAlias::THEME_SWITCHER_DARK_BUTTON,
            'system' => \Filament\View\PanelsIconAlias::THEME_SWITCHER_SYSTEM_BUTTON,
        })
    }}
</button>
