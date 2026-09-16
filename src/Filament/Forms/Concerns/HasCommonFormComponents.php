<?php

namespace Wsmallnews\Support\Filament\Forms\Concerns;

use Filament\Forms;

/**
 * 公共表单组件工厂
 *
 * 统一 enum 开关（ToggleButtons）与 order_column 字段（TextInput）的组件形态，
 * 配套规范见 boost guidelines「Filament Resource 风格统一」。
 */
trait HasCommonFormComponents
{
    /**
     * 创建 enum ToggleButtons 组件（inline + grouped，options 取枚举，默认第一个 case）
     *
     * 主要用于状态字段（label 默认「状态」），其他 enum 场景（性别、类型等）在外层 ->label() 覆盖
     *
     * @param  class-string<\BackedEnum>  $enumClass  枚举类名
     * @param  string  $field  字段名称
     * @param  string | null  $label  字段标签，缺省取翻译
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
     * 创建 order_column 排序输入组件（integer + min:0，留空自动分配到末尾）
     *
     * @param  string  $field  字段名称
     * @param  string | null  $label  字段标签，缺省取翻译
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
