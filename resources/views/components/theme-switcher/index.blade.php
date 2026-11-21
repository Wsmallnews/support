@php
    use Filament\Support\Icons\Heroicon;
@endphp

<div
    x-data="{ theme: null }"
    x-init="
        $watch('theme', () => {
            $dispatch('sn-support-frontend-theme-changed', theme)
        })

        theme = localStorage.getItem(window.__snThemeKey || 'sn-support-frontend-theme') || 'system'
    "
    class="sn-support-theme-switcher grid grid-cols-3 gap-0.5"
>
    <x-sn-support::theme-switcher.button
        :icon="Heroicon::Sun"
        theme="light"
    />

    <x-sn-support::theme-switcher.button
        :icon="Heroicon::Moon"
        theme="dark"
    />

    <x-sn-support::theme-switcher.button
        :icon="Heroicon::ComputerDesktop"
        theme="system"
    />
</div>
