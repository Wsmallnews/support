@props([
    'field',
])

@if (in_array($field['type'], ['checkbox', 'radio']))
    @if ($field['type'] == 'checkbox')
        <x-filament::input.checkbox
            :placeholder="$field['placeholder'] ?? $field['label']"
            x-model="value"
        />
    @elseif ($field['type'] == 'radio')
        <div class="flex items-center">
            @foreach ($field['options'] as $option)
                <div class="flex items-center">
                    <x-filament::input.radio
                    name="{{ $field['field'] }}"
                    :value="$option['value']"
                    x-model="value"
                />
                    <label class="ml-2" for="{{ $field['field'] }}_{{ $option['value'] }}">{{ $option['label'] }}</label>
                </div>
            @endforeach
        </div>
    @endif
@else 
    <x-filament::input.wrapper
    >
        @if ($field['type'] == 'text')
            <x-filament::input
                type="text"
                :placeholder="$field['placeholder'] ?? $field['label']"
                x-model="value"
            />
        @elseif ($field['type'] == 'number')
            <x-filament::input
                type="number"
                :placeholder="$field['placeholder'] ?? $field['label']"
                x-model="value"
            />
        @elseif ($field['type'] == 'select')
            <x-filament::input.select
                :placeholder="$field['placeholder'] ?? $field['label']"
                x-model="value"
            >
                @foreach ($field['options'] as $option)
                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                @endforeach
            </x-filament::input.select>
        @endif
    </x-filament::input.wrapper>
@endif
