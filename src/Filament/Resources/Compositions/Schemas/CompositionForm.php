<?php

namespace Wsmallnews\Support\Filament\Resources\Compositions\Schemas;

use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Str;
use Livewire\Component;
use Wsmallnews\Support\Enums\CompositionStatus;
use Wsmallnews\Support\Facades\CompositionRegistry;
use Wsmallnews\Support\Features\Composition\CompositionRenderer;
use Wsmallnews\Support\Filament\Forms\FormComponents;

class CompositionForm
{
    public static function configure(Schema $schema, ?string $moduleId = null): Schema
    {
        return $schema
            ->components([
                ...static::forms($moduleId),
            ]);
    }

    /**
     * @param  string|null  $moduleId  模块标识（插件 id）：组件类型下拉的数据来源（CompositionRegistry 注册 key）
     */
    public static function forms(?string $moduleId = null): array
    {
        return [
            Schemas\Components\Section::make(__('sn-support::composition.form.basic_info'))->schema([
                Forms\Components\TextInput::make('title')->label(__('sn-support::composition.form.title'))
                    ->placeholder(__('sn-support::composition.form.title_placeholder'))
                    ->required(),
                FormComponents::orderColumnInput(),
                FormComponents::statusToggleButtons(CompositionStatus::class),
            ])->columns(2)->columnSpanFull(),

            Schemas\Components\Section::make(__('sn-support::composition.form.layout_section'))
                ->schema([
                    static::rowsRepeater($moduleId),
                ])
                ->columns(1)
                ->columnSpanFull(),
        ];
    }

    /**
     * 行式布局编排：每个区块（行）选择 lg+ 分栏方式，往对应栏里添加组件；
     * lg 以下固定单列，按 左栏 → 右栏 顺序堆叠（无需设置）
     */
    protected static function rowsRepeater(?string $moduleId): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('rows')
            ->label(__('sn-support::composition.form.rows'))
            ->schema(fn () => static::rowSchema($moduleId))
            ->itemLabel(fn (array $state): string => static::layoutOptions()[$state['layout']] ?? '')
            ->addActionLabel(__('sn-support::composition.form.add_row'))
            ->defaultItems(0)               // 空编排合法（渲染回退空状态），不预置空行
            ->collapsible()
            ->cloneable()
            ->addActionAlignment(Alignment::Start)
            ->columnSpanFull()
            ->statePath('components');
    }

    /**
     * 单个区块（行）的 schema：布局选择 + 左右栏组件。
     * 栏容器为三等分栅格，槽位宽度与前台渲染比例一致（所见即所得）
     */
    protected static function rowSchema(?string $moduleId): array
    {
        return [
            Forms\Components\ToggleButtons::make('layout')
                ->label(__('sn-support::composition.form.layout'))
                ->options(static::layoutOptions())
                ->default(CompositionRenderer::LAYOUT_FULL)
                ->helperText(__('sn-support::composition.form.layout_helper'))
                ->inline()
                ->grouped()
                ->live()
                ->required()
                ->columnSpanFull(),

            Schemas\Components\Grid::make(3)->schema([
                static::slotRepeater('left', __('sn-support::composition.form.slot_left'), $moduleId)
                    ->columnSpan(fn (Get $get) => match ($get('../layout')) {
                        CompositionRenderer::LAYOUT_LEFT_NARROW => 1,
                        CompositionRenderer::LAYOUT_LEFT_WIDE => 2,
                        default => 'full',
                    }),

                static::slotRepeater('right', __('sn-support::composition.form.slot_right'), $moduleId)
                    ->columnSpan(fn (Get $get) => match ($get('../layout')) {
                        CompositionRenderer::LAYOUT_LEFT_NARROW => 2,
                        CompositionRenderer::LAYOUT_LEFT_WIDE => 1,
                        default => 'full',
                    })
                    ->visibleJs(<<<'JS'
                        $get('layout') != 'full'
                    JS),
            ])->columnSpanFull(),
        ];
    }

    /**
     * 栏（槽）Repeater：通栏时仅左栏生效（作为内容区渲染，占满整行）
     */
    protected static function slotRepeater(string $slot, string $label, ?string $moduleId): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make($slot)
            ->label($label)
            ->schema(fn () => static::componentItems($moduleId))
            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
            ->addActionLabel(__('sn-support::composition.form.add_component'))
            ->defaultItems(0)               // 不预置空条目，避免 required 的组件类型挡住保存；右侧栏虽经 visibleJs 隐藏，服务端校验仍会命中
            ->collapsible()
            ->cloneable();
    }

    /**
     * 组件条目 schema：类型（CompositionRegistry 注册项）+ 标题 + 描述 + 类型对应参数表单。
     * 注册表 forms 闭包的 fields 参数需要整表根状态，经注入的 Livewire 组件取 data（避免深层相对路径上溯）
     */
    protected static function componentItems(?string $moduleId): array
    {
        $uuid = Str::uuid();

        return [
            Forms\Components\Select::make('type')
                ->label(__('sn-support::composition.form.component_type'))
                ->placeholder(__('sn-support::composition.form.component_type_placeholder'))
                ->options(fn (): array => filled($moduleId) ? CompositionRegistry::getTypesOptions($moduleId) : [])
                ->live()
                ->required()
                ->afterStateUpdated(function (Forms\Components\Select $component, $state, Set $set) use ($uuid, $moduleId) {
                    // 默认设置内容类型 label
                    $set('label', filled($moduleId) ? (CompositionRegistry::getTypesOptions($moduleId)[$state] ?? '') : '');

                    // 填充组件特定字段
                    return $state && $component
                        ->getContainer()
                        ->getComponent('dynamicExtrasFields_' . $uuid)       // 当 dynamicExtrasFields visible = false, 也就是不可见时， 这里获取的是 null
                        ?->getChildSchema()
                        ->fill();
                }),

            Forms\Components\TextInput::make('label')
                ->label(__('sn-support::composition.form.component_label'))
                ->live(onBlur: true)
                ->placeholder(__('sn-support::composition.form.component_label_placeholder')),

            Forms\Components\TextInput::make('description')
                ->label(__('sn-support::composition.form.component_description'))
                ->live(onBlur: true)
                ->placeholder(__('sn-support::composition.form.component_description_placeholder')),

            Schemas\Components\Fieldset::make('extras')
                ->label(__('sn-support::composition.form.component_options'))
                ->schema(function (Get $get, Component $livewire) use ($moduleId) {
                    return filled($moduleId) && filled($get('type')) ? CompositionRegistry::getTypeForms($moduleId, $get('type'), ['fields' => $livewire->data]) : [];
                })->visible(function (Get $get, Component $livewire) use ($moduleId) {
                    $hasForms = filled($moduleId) && filled($get('type')) ? CompositionRegistry::hasTypeForms($moduleId, $get('type'), ['fields' => $livewire->data]) : false;

                    // 选了内容类型，并且内容类型有 form 表单
                    return filled($moduleId) && filled($get('type')) && $hasForms;
                })
                ->columns(['md' => 2])
                ->columnSpanFull()
                ->statePath('extras')
                ->key('dynamicExtrasFields_' . $uuid),
        ];
    }

    /**
     * 布局选项（label 与 CompositionRenderer 布局常量一一对应）
     */
    protected static function layoutOptions(): array
    {
        return [
            CompositionRenderer::LAYOUT_FULL => __('sn-support::composition.form.layout_full'),
            CompositionRenderer::LAYOUT_LEFT_NARROW => __('sn-support::composition.form.layout_left_narrow'),
            CompositionRenderer::LAYOUT_LEFT_WIDE => __('sn-support::composition.form.layout_left_wide'),
        ];
    }
}
