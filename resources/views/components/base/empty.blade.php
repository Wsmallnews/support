@props([
    'tag' => 'div',
    'href' => null,
])

<{{ $tag }}
    {{ ($tag == 'a' && $href) ? \Filament\Support\generate_href_html($href) : '' }}
    {{ $attributes->merge(['class' => '']) }}
>
    {{ $slot }}
</{{ $tag }}>