<?php

namespace Wsmallnews\Support\Filament\Forms\Concerns;

use Filament\Forms;

/**
 * Shared form component factories: enum toggles and the order_column input.
 * See the Filament conventions section of the core guidelines.
 */
trait HasCommonFormComponents
{
    /**
    * Enum ToggleButtons (inline, grouped, defaulting to the first case). Intended for status
    * fields; other enums should override the label at the call site.
    *
    * @param  class-string<\BackedEnum>  $enumClass  Enum class
    * @param  string  $field  Column name
    * @param  string | null  $label  Field label, defaults to the translation
    */
    public static function enumsToggleButtons(string $enumClass, string $field = 'status', ?string $label = null): Forms\Components\ToggleButtons
    {
        return Forms\Components\ToggleButtons::make($field)
            ->label($label ?? __('sn-support::support.form_components.status.label'))
            ->inline()
            ->grouped()
            ->options($enumClass)
            ->default($enumClass::cases()[0] ?? null);
    }

    /**
    * order_column input (integer, min 0, left empty to append at the end).
    *
    * @param  string  $field  Column name
    * @param  string | null  $label  Field label, defaults to the translation
    */
    public static function orderColumnInput(string $field = 'order_column', ?string $label = null): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make($field)
            ->label($label ?? __('sn-support::support.form_components.order_column.label'))
            ->integer()
            ->minValue(0)
            ->helperText(__('sn-support::support.form_components.order_column.helper'));
    }
}
