@props([
    'page',
    'rows' => [],
])

{{-- Three branches: render the bound composition, else the page's own content, else empty.
Shared by the support page component and the cms home page. --}}
<div class="w-full flex flex-col sn-gap">
    @if (filled($rows))
        <x-sn-support::composition.rows :rows="$rows" />
    @elseif (blank($page->composition_id) && $page->content?->content)
        <div class="sn-container sn-padded">
            <x-sn-support::content
                :contentType="$page->content->content_type"
                :content="$page->content->content"
            />
        </div>
    @else
        <x-sn-support::empty
            :icon="\Filament\Support\Icons\Heroicon::OutlinedDocumentText"
            icon-color="gray"
            :heading="$page->title"
            :description="__('sn-support::page.frontend.empty')"
        />
    @endif
</div>
