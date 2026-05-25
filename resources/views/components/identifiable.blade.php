@props([
    'identifiable' => null,
    'contained' => false,
    'isLink' => false,
])

@php
    use Filament\Support\Icons\HeroIcon;
    use Wsmallnews\Support\Contracts\HasSnIdentifiable;
    use Wsmallnews\Support\Exceptions\IdentifiableException;

    if (!$identifiable instanceof HasSnIdentifiable) {
        throw new IdentifiableException(get_class($identifiable) . ' model must implement `\Wsmallnews\Support\Contracts\HasSnIdentifiable` interface.');
    }
@endphp

<div 
    {{
        $attributes->class([
            'sn-container p-4' => $contained,
            'sn-hover sn-link' => $isLink,
            'flex items-center gap-4 justify-between group',
        ])
    }}
>
    <div class="w-12 h-12 rounded-full shrink-0 overflow-hidden bg-gray-100 dark:bg-gray-800">
        @if($identifiable->getSnAvatarUrl())
            <img class="w-full h-full object-cover" src="{{ $identifiable->getSnAvatarUrl() }}" alt="{{ $identifiable->getSnName() }}" />
        @else
            <div class="sn-image-placeholder">
                <x-filament::icon :icon="Heroicon::User" class="w-full h-full" aria-hidden="true" />
            </div>
        @endif
    </div>

    <div class="flex flex-col grow gap-1">
        <span class="sn-content-text">
            {{ $identifiable->getSnName() }}
        </span>
        <span class="sn-descript-text">
            {{ $identifiable->getSnEmail() }}
        </span>
    </div>

    @if ($isLink)
        <div class="sn-gray-text shrink-0 hidden group-hover:block">
            <x-filament::icon :icon="Heroicon::ChevronRight" class="size-5" aria-hidden="true" />
        </div>
    @endif
</div>