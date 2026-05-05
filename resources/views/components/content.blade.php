@php
    use Wsmallnews\Support\Enums\ContentType;
@endphp

@props([
    'content' => null,                    // 内容
    'contentType' => ContentType::Textarea,          // 内容类型，  textarea, markdown or richtext
])

<div class="sn-content-container">
    @if ($contentType == ContentType::Markdown)
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