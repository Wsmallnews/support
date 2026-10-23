{{-- Inline submit button, wrapped as HtmlString and rendered in the wrapper suffix.
Styles live in .sn-search-submit. type=button calls $wireClick; type=submit serves forms. --}}
@php
    /** @var string $type */
/** @var string|null $wireClick */
@endphp
<button type="{{ $type }}" @if (filled($wireClick)) wire:click="{{ $wireClick }}" @endif class="sn-search-submit">
    {{ __('sn-support::search.search_button') }}
</button>
