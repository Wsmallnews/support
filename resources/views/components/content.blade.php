@php
    use Wsmallnews\Support\Enums\ContentType;

    // Image content is a JSON array of paths.
    if ($contentType == ContentType::Images) {
        $decoded = is_array($content) ? $content : json_decode((string) ($content ?? ''), true);

        $content = array_values(array_filter(is_array($decoded) ? $decoded : [], fn ($path) => filled($path)));
    }
@endphp

<div class="sn-content-container">
    @if ($contentType == ContentType::Images)
        {{-- Long-form image stack: full width, no gaps --}}
        @if (count($content))
            <div class="sn-content-images w-full">
                @foreach ($content as $path)
                    <img
                        src="{{ $path instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile ? $path->temporaryUrl() : files_url($path) }}"
                        class="block w-full"
                        loading="lazy"
                        alt=""
                    />
                @endforeach
            </div>
        @endif
    @elseif ($contentType == ContentType::Markdown)
        <x-markdown class="fi-prose sn-prose">
            {!! $content !!}
        </x-markdown>
    @elseif ($contentType == ContentType::Richtext)
        <div class="fi-prose sn-prose">
            {!! $content !!}
        </div>
    @else
        <div class="sn-content-text">
            {!! $content !!}
        </div>
    @endif
</div>
