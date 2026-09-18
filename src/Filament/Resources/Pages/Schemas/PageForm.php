<?php

namespace Wsmallnews\Support\Filament\Resources\Pages\Schemas;

use Filament\Forms;
use Filament\Schemas;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Unique;
use Livewire\Component;
use Wsmallnews\Support\Enums\PageStatus;
use Wsmallnews\Support\Filament\Forms\FormComponents;
use Wsmallnews\Support\Support\Utils as SupportUtils;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ...static::forms(),
            ]);
    }

    public static function forms(): array
    {
        return [
            Schemas\Components\Section::make(__('sn-support::page.form.basic_info'))->schema([
                Forms\Components\TextInput::make('title')->label(__('sn-support::page.form.title'))
                    ->required()
                    ->maxLength(60),

                Forms\Components\TextInput::make('slug')->label(__('sn-support::page.form.slug'))
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule) => $rule->whereNull('deleted_at'))
                    ->helperText(__('sn-support::page.form.slug_helper')),

                FormComponents::orderColumnInput(),
                FormComponents::enumsToggleButtons(PageStatus::class),

                // 绑定编排（可选高级模式）：前台页面渲染该编排的组件行；未绑定时直接编辑下方页面内容，两者互斥
                Forms\Components\Select::make('composition_id')->label(__('sn-support::page.form.composition'))
                    ->options(function (Component $livewire): array {
                        // 资源页面（CreatePage/EditPage）均 use Pages\Scopeable，直接读取（与 NavigationForm 同模式）
                        $scopeable = $livewire->getScopeable();

                        return SupportUtils::getCompositionModel()::query()
                            ->where('scope_type', $scopeable['scope_type'] ?? null)
                            ->where('scope_id', $scopeable['scope_id'] ?? 0)
                            ->orderBy('order_column')
                            ->pluck('title', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->helperText(__('sn-support::page.form.composition_helper')),

                // 页面内容（主体通道）：未绑定编排时编辑；绑定后隐藏（渲染时编排优先，忽略内容）
                FormComponents::contentTypeGroup(required: false)
                    ->visible(fn (Get $get): bool => blank($get('composition_id')))
                    ->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),
        ];
    }
}
