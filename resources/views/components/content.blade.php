@props([
    'content' => null,                    // 内容
    'contentType' => 'textarea',          // 内容类型，  textarea, markdown or richtext
])

<div class="sn-content-container">
    @if ($contentType == 'markdown')
        <x-markdown class="fi-prose sn-prose">
            {!! $content !!}
        </x-markdown>
    @elseif ($contentType == 'richtext')
        <div class="fi-prose sn-prose">
            {!! $content !!}
        </div>
    @else
        <div class="sn-content-text">
            {!! $content !!}
        </div>
    @endif
</div>