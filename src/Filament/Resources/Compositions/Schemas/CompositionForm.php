<?php

namespace Wsmallnews\Support\Filament\Resources\Compositions\Schemas;

use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
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
     * @param  string|null  $moduleId  模块标识（插件 id）：组件类型与用途槽位下拉的数据来源（CompositionRegistry 注册 key）
     */
    public static function forms(?string $moduleId = null): array
    {
        return [
            Schemas\Components\Section::make(__('sn-support::composition.form.basic_info'))->schema([
                Forms\Components\TextInput::make('title')->label(__('sn-support::composition.form.title'))
                    ->placeholder(__('sn-support::composition.form.title_placeholder'))
                    ->required(),
                FormComponents::orderColumnInput(),
                FormComponents::enumsToggleButtons(CompositionStatus::class),

                // purpose 槽位（可选）：空 = 通用展示编排（可被 Page 绑定）；选模块槽位后参与槽位匹配
                // （同槽位多条已发布编排时渲染 order_column 最前的一条），白名单由各模块经 Registry 注册
                Forms\Components\Select::make('purpose')
                    ->label(__('sn-support::composition.form.purpose'))
                    ->placeholder(__('sn-support::composition.form.purpose_placeholder'))
                    ->options(fn (): array => filled($moduleId) ? CompositionRegistry::getPurposes($moduleId)->toArray() : [])
                    ->helperText(__('sn-support::composition.form.purpose_helper'))
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) use ($moduleId) {
                        // 切换槽位：位置重置为新槽位默认位置，并按新模式同步行布局
                        $meta = filled($moduleId) && filled($get('purpose'))
                            ? CompositionRegistry::getPurpose($moduleId, $get('purpose'))
                            : null;
                        $position = $meta['default'] ?? null;
                        $set('options.position', $position);
                        static::syncRowsToLayoutMode($moduleId, $get, $set, $get('purpose'), $position);
                    }),

                // 槽位位置：选项/默认值读槽位注册的 positions/default（label 为翻译键或纯文本，此处统一 __()；
                // 标准位置键在 sn-support::composition.position.*，自定义位置键由注册模块提供）。
                // 槽位只声明一个位置时无需选择，隐藏
                Forms\Components\ToggleButtons::make('options.position')
                    ->label(__('sn-support::composition.form.position'))
                    ->options(function (Get $get) use ($moduleId): array {
                        $meta = filled($moduleId) && filled($get('purpose'))
                            ? CompositionRegistry::getPurpose($moduleId, $get('purpose'))
                            : null;

                        return collect($meta['positions'] ?? [])
                            ->map(fn (string $label): string => __($label))
                            ->all();
                    })
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) use ($moduleId) {
                        // 位置切换改变布局模式（如 上→左）：进入堆叠模式时同步行布局
                        static::syncRowsToLayoutMode($moduleId, $get, $set, $get('purpose'), $get('options.position'));
                    })
                    ->visible(function (Get $get) use ($moduleId): bool {
                        $meta = filled($moduleId) && filled($get('purpose'))
                            ? CompositionRegistry::getPurpose($moduleId, $get('purpose'))
                            : null;

                        return count($meta['positions'] ?? []) > 1;
                    })
                    ->inline()
                    ->grouped(),
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
     * lg 以下固定单列，按 左栏 → 右栏 顺序堆叠（无需设置）。
     * 堆叠模式（侧栏类槽位）：隐藏分栏开关，每行强制通栏，按钮文案改为「添加侧栏块」
     */
    protected static function rowsRepeater(?string $moduleId): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('rows')
            ->label(__('sn-support::composition.form.rows'))
            ->schema(fn () => static::rowSchema($moduleId))
            ->itemLabel(fn (array $state): string => static::layoutOptions()[$state['layout'] ?? null] ?? '')
            ->addActionLabel(fn (Component $livewire): string => static::layoutMode($moduleId, $livewire) === CompositionRenderer::LAYOUT_MODE_STACK
                ? __('sn-support::composition.form.add_sidebar_block')
                : __('sn-support::composition.form.add_row'))
            ->defaultItems(0)               // 空编排合法（渲染回退空状态），不预置空行
            ->collapsible()
            ->cloneable()
            ->addActionAlignment(Alignment::Start)
            ->columnSpanFull()
            ->statePath('components');
    }

    /**
     * 单个区块（行）的 schema：布局选择 + 左右栏组件。
     * 栏容器为三等分栅格，槽位宽度与前台渲染比例一致（所见即所得）。
     * 堆叠模式（侧栏类槽位）下隐藏分栏开关（行恒为通栏），右栏不渲染
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
                ->visible(fn (Component $livewire): bool => static::layoutMode($moduleId, $livewire) === CompositionRenderer::LAYOUT_MODE_ROWS)
                ->columnSpanFull(),

            Schemas\Components\Grid::make(3)->schema([
                static::slotRepeater('left', __('sn-support::composition.form.slot_left'), $moduleId)
                    ->columnSpan(fn (Get $get) => match ($get('layout')) {
                        CompositionRenderer::LAYOUT_LEFT_NARROW => 1,
                        CompositionRenderer::LAYOUT_LEFT_WIDE => 2,
                        default => 'full',
                    }),

                static::slotRepeater('right', __('sn-support::composition.form.slot_right'), $moduleId)
                    ->columnSpan(fn (Get $get) => match ($get('layout')) {
                        CompositionRenderer::LAYOUT_LEFT_NARROW => 2,
                        CompositionRenderer::LAYOUT_LEFT_WIDE => 1,
                        default => 'full',
                    })
                    // 通栏布局右栏本就不渲染（visibleJs）；堆叠模式（侧栏槽位）服务端兜底隐藏（已有数据保留，切回行式不丢）
                    ->visible(fn (Component $livewire): bool => static::layoutMode($moduleId, $livewire) === CompositionRenderer::LAYOUT_MODE_ROWS)
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
        return [
            Forms\Components\Select::make('type')
                ->label(__('sn-support::composition.form.component_type'))
                ->placeholder(__('sn-support::composition.form.component_type_placeholder'))
                ->options(fn (): array => filled($moduleId) ? CompositionRegistry::getTypesOptions($moduleId) : [])
                ->live()
                ->required()
                ->afterStateUpdated(function (Get $get, Set $set, Component $livewire) use ($moduleId) {
                    // 默认设置内容类型 label
                    $set('label', filled($moduleId) ? (CompositionRegistry::getTypesOptions($moduleId)[$get('type')] ?? '') : '');

                    // 官方文档的动态字段模式（live Select + schema 闭包，见 extras Fieldset）在本场景缺一环：
                    // 注册表字段挂在 statePath('extras') 容器下且嵌于双层 Repeater，切类型/新建条目时 extras 为 null，
                    // 前端 entangle 无法在 null 上写字段键（实测选中值丢失、保存报 required）。
                    // 这里按新类型的注册表单显式初始化 extras（字段名为键 + 默认值），同时清除旧类型的残留参数
                    $fields = filled($moduleId) && filled($get('type'))
                        ? CompositionRegistry::getTypeForms($moduleId, $get('type'), ['fields' => $livewire->data])
                        : [];
                    $extras = [];
                    foreach ($fields as $field) {
                        $extras[$field->getName()] = $field->getDefaultState();
                    }
                    if ($extras !== []) {
                        $set('extras', $extras);
                    }
                }),

            Forms\Components\TextInput::make('label')
                ->label(__('sn-support::composition.form.component_label'))
                ->live(onBlur: true)
                ->placeholder(__('sn-support::composition.form.component_label_placeholder')),

            Forms\Components\TextInput::make('description')
                ->label(__('sn-support::composition.form.component_description'))
                ->live(onBlur: true)
                ->placeholder(__('sn-support::composition.form.component_description_placeholder')),

            // 前台显示设置：块头（标题/描述）与外层容器均可按条目关闭（标题/描述保留在后台用于区分条目）
            Forms\Components\ToggleButtons::make('show_header')
                ->label(__('sn-support::composition.form.show_header'))
                ->boolean()
                ->default(true)
                ->inline()
                ->grouped()
                ->helperText(__('sn-support::composition.form.show_header_helper')),

            Forms\Components\ToggleButtons::make('contained')
                ->label(__('sn-support::composition.form.contained'))
                ->boolean()
                ->default(true)
                ->inline()
                ->grouped()
                ->helperText(__('sn-support::composition.form.contained_helper')),

            Schemas\Components\Fieldset::make('extras')
                ->label(__('sn-support::composition.form.component_options'))
                ->schema(function (Get $get, Component $livewire) use ($moduleId) {
                    return filled($moduleId) && filled($get('type')) ? CompositionRegistry::getTypeForms($moduleId, $get('type'), ['fields' => $livewire->data]) : [];
                })->visible(function (Get $get, Component $livewire) use ($moduleId) {
                    $hasForms = filled($moduleId) && filled($get('type')) ? CompositionRegistry::hasTypeForms($moduleId, $get('type'), ['fields' => $livewire->data]) : false;

                    // 选了内容类型，并且内容类型有 form 表单
                    return filled($moduleId) && filled($get('type')) && $hasForms;
                })
                ->columns(1)          // 单列：编排槽位有宽有窄（左一右二），管理表单列数无容器查询，两列会把窄槽里的选择器挤成半列
                ->columnSpanFull()
                ->statePath('extras')
                ->key('dynamicExtrasFields'),    // 固定相对 key：绝对 key 自动拼条目 uuid（跨请求稳定），随机 uuid 会导致下拉往返时 DOM 重建、闪关
        ];
    }

    /**
     * 当前编辑布局模式（rows 行式分栏 | stack 单列堆叠）。
     * 经注入的 Livewire 组件读整表根状态（purpose/options.position 在根级，
     * 行内字段的深层相对路径上溯在双层 Repeater 下不可靠）
     */
    protected static function layoutMode(?string $moduleId, Component $livewire): string
    {
        $data = $livewire->data ?? [];

        return CompositionRegistry::getLayoutMode($moduleId, $data['purpose'] ?? null, $data['options']['position'] ?? null);
    }

    /**
     * 位置/槽位切换后按新模式同步行布局：进入堆叠模式时所有行强制通栏
     * （侧栏槽位单列；右栏残留数据保留在状态中，切回行式模式不丢）
     */
    protected static function syncRowsToLayoutMode(?string $moduleId, Get $get, Set $set, ?string $purpose, ?string $position): void
    {
        if (CompositionRegistry::getLayoutMode($moduleId, $purpose, $position) !== CompositionRenderer::LAYOUT_MODE_STACK) {
            return;
        }

        $rows = collect($get('components') ?? [])
            ->map(fn (array $row): array => [...$row, 'layout' => CompositionRenderer::LAYOUT_FULL])
            ->all();

        $set('components', $rows);
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
