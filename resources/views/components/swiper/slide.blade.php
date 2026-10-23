@props([
    'item' => [],      // Shorthand: [image, href, html]
    'image' => null,   // Image URL, wins over item
    'href' => null,    // Link, wins over item
    'html' => null,    // Fully custom slide content, wins over item
])

@aware([
    'imageFit' => 'cover',  // Inherited from the parent swiper
])

@php
    use Filament\Support\Facades\FilamentView;

    $image = $image ?? ($item['image'] ?? null);
    $href = $href ?? ($item['href'] ?? null);
    $html = $html ?: ($item['html'] ?? null);

    $spaMode = FilamentView::hasSpaMode();
@endphp

<div
    {{ $attributes->class([
        'swiper-slide',
        'cursor-pointer' => $href,
    ]) }}
    data-slide-image="{{ $image }}"
    @if ($href)
        @click="jumpToUrl('{{ $href }}', @js($spaMode))"
    @endif
>
    @if ($html)
        {!! $html !!}
    @else
        <img src="{{ $image }}" loading="lazy" class="w-full h-full {{ $imageFit === 'cover' ? 'object-cover' : 'object-contain' }}" />
    @endif

    {{-- Overlay content; positioning and styling belong to the caller --}}
    {{ $slot }}
</div>
