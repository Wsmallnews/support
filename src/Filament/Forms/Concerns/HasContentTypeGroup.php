<?php

namespace Wsmallnews\Support\Filament\Forms\Concerns;

use Filament\Actions\Action;
use Filament\Forms;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Wsmallnews\Support\Enums\ContentType;
use Wsmallnews\Support\Support\Utils as SupportUtils;

/**
 * 内容类型切换表单组（contentTypeGroup）
 *
 * 依赖宿主类的 richEditor() / markdownEditor() / plainImageUpload() 工厂方法
 * （见 FormComponents），编辑器统一应用 form_components.editor / upload 配置。
 */
trait HasContentTypeGroup
{
    // ========================= 对外工厂方法 =========================

    /**
     * 创建一个内容类型切换表单组（morphOne content 关联 + 虚拟字段映射）
     *
     * 通过 content_type 切换编辑器（textarea / richtext / markdown / images），内容统一写入
     * 关联表的 content 列；images 类型存 JSON 路径数组。保存逻辑封装在组件内部，
     * 调用方的 Create / Edit 页面无需任何 content 处理代码。
     *
     * 类型与默认值由调用方直接传入（通常从各模块自己的配置读取），
     * 也可以在特定场景直接传数组，不经过配置；调用方不传时回退到
     * sn-support.form_components.content 全局默认配置。
     *
     * @param  array<int, ContentType|string>|null  $types  允许的内容类型，null = 全局配置或全部类型
     * @param  ContentType|string|null  $defaultType  默认内容类型，null = 全局配置或 Richtext（不在允许列表时取第一个）
     * @param  string  $relationship  内容关联名称
     * @param  string|null  $label  内容字段标签（默认取 support 翻译）
     * @param  string|null  $directory  编辑器附件 / 纯图上传目录
     * @param  bool  $required  内容是否必填（false 时空内容不创建 / 删除关联记录）
     */
    public static function contentTypeGroup(
        ?array $types = null,
        ContentType | string | null $defaultType = null,
        string $relationship = 'content',
        ?string $label = null,
        ?string $directory = null,
        bool $required = true,
    ): Group {
        $types = static::resolveContentTypes($types);
        $defaultType = static::resolveContentTypeDefault($defaultType, $types);

        $typeButtons = Forms\Components\ToggleButtons::make('content_type')
            ->label(__('sn-support::support.form_components.content.type_label'))
            ->options(collect($types)->mapWithKeys(fn (ContentType $type): array => [$type->value => (string) $type->getLabel()])->all())
            ->enum(ContentType::class)
            ->default($defaultType)
            ->inline()
            ->grouped()
            ->live();

        $detailLabel = $label ?? __('sn-support::support.form_components.content.detail_label');

        $editors = [
            // 只有一种类型时隐藏切换按钮，用 Hidden 保持 content_type 的填充与脱水
            count($types) === 1
                ? Forms\Components\Hidden::make('content_type')->default($defaultType)
                : $typeButtons,
            Forms\Components\Hidden::make('content'),
        ];

        foreach ($types as $type) {
            $previewAction = static::previewContentAction();

            $editors[] = match ($type) {
                ContentType::Textarea => Forms\Components\Textarea::make('content_textarea')
                    ->label($detailLabel)
                    ->placeholder(__('sn-support::support.form_components.content.placeholder'))
                    ->rows(5)
                    ->autosize()
                    ->required($required)
                    ->hintAction($previewAction)
                    ->visible(fn (Get $get): bool => static::normalizeContentType($get('content_type')) === ContentType::Textarea),
                ContentType::Richtext => static::richEditor('content_richtext')
                    ->label($detailLabel)
                    ->placeholder(__('sn-support::support.form_components.content.placeholder'))
                    ->fileAttachmentsDirectory($directory)
                    ->required($required)
                    ->hintAction($previewAction)
                    ->visible(fn (Get $get): bool => static::normalizeContentType($get('content_type')) === ContentType::Richtext),
                ContentType::Markdown => static::markdownEditor('content_markdown')
                    ->label($detailLabel)
                    ->placeholder(__('sn-support::support.form_components.content.markdown_placeholder'))
                    ->fileAttachmentsDirectory($directory)
                    ->required($required)
                    ->hintAction($previewAction)
                    ->visible(fn (Get $get): bool => static::normalizeContentType($get('content_type')) === ContentType::Markdown),
                ContentType::Images => static::plainImageUpload('content_images')
                    ->label($detailLabel)
                    ->directory($directory)
                    ->multiple()
                    ->required($required)
                    ->panelLayout('compact')        // 竖着排列，每张图片占一行
                    ->hintAction($previewAction)
                    ->visible(fn (Get $get): bool => static::normalizeContentType($get('content_type')) === ContentType::Images),
            };
        }

        return Group::make()
            ->relationship($relationship)
            ->mutateRelationshipDataBeforeFillUsing(fn (array $data): array => static::fillVirtualContentField($data))
            ->saveRelationshipsBeforeChildrenUsing(function (Group $component) use ($relationship, $required, $defaultType): void {
                $data = $component->getChildSchema()->getState(shouldCallHooksBefore: false);

                $type = static::normalizeContentType($data['content_type'] ?? null) ?? $defaultType;
                $value = $data['content_' . $type->value] ?? null;

                // 富文本空内容（如空段落 <p></p>）与其他类型的空值一样视为空白
                $isBlank = $type === ContentType::Richtext
                    ? blank(trim(strip_tags((string) $value)))
                    : blank($value);

                // 可选模式下内容为空：不创建关联记录，已有记录则删除
                if ((! $required) && $isBlank) {
                    $component->getCachedExistingRecord()?->delete();

                    return;
                }

                $record = $component->getRecord()->{$relationship}()->updateOrCreate([], static::mapVirtualContentField($data, $type));
                $component->cachedExistingRecord($record);
            })
            ->schema($editors)
            ->columns(1);
    }

    // ========================= 预览操作 =========================

    /**
     * 内容预览操作：按当前 content_type 读取虚拟字段值，用 content 组件弹窗渲染
     */
    protected static function previewContentAction(): Action
    {
        return Action::make('previewContent')
            ->label(__('sn-support::support.form_components.content.preview'))
            ->icon(Heroicon::OutlinedEye)
            ->modal()
            ->modalHeading(__('sn-support::support.form_components.content.preview'))
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('sn-support::support.table.column.content_close'))
            ->modalContent(function (Get $get) {
                $type = static::normalizeContentType($get('content_type'));

                if (! $type) {
                    return null;
                }

                return view('sn-support::components.content', [
                    'contentType' => $type,
                    'content' => $get('content_' . $type->value),
                ]);
            });
    }

    // ========================= 类型解析 =========================

    /**
     * 解析允许的内容类型：显式参数 > support 全局配置（form_components.content.types） > 全部类型
     *
     * @param  array<int, ContentType|string>|null  $types
     * @return array<int, ContentType>
     */
    protected static function resolveContentTypes(?array $types): array
    {
        $configTypes = $types ?? SupportUtils::getConfig('form_components.content.types');

        $resolved = collect(is_array($configTypes) ? $configTypes : ContentType::cases())
            ->map(fn (mixed $type): ?ContentType => static::normalizeContentType($type))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return filled($resolved) ? $resolved : ContentType::cases();
    }

    /**
     * 解析默认内容类型：显式参数 > support 全局配置（form_components.content.default_type） > Richtext，
     * 并保证其属于允许类型列表
     *
     * @param  array<int, ContentType>  $types
     */
    protected static function resolveContentTypeDefault(ContentType | string | null $defaultType, array $types): ContentType
    {
        $defaultType = static::normalizeContentType($defaultType)
            ?? static::normalizeContentType(SupportUtils::getConfig('form_components.content.default_type'))
            ?? ContentType::Richtext;

        return in_array($defaultType, $types, true) ? $defaultType : $types[0];
    }

    /**
     * 内容类型规范化（兼容枚举实例与字符串值）
     */
    protected static function normalizeContentType(mixed $type): ?ContentType
    {
        return match (true) {
            $type instanceof ContentType => $type,
            is_string($type) && filled($type) => ContentType::tryFrom($type),
            default => null,
        };
    }

    // ========================= 虚拟字段映射 =========================

    /**
     * 关联记录 content 列回填到当前类型的虚拟字段（images 解码为数组）
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function fillVirtualContentField(array $data): array
    {
        $type = static::normalizeContentType($data['content_type'] ?? null);

        if (! $type) {
            return $data;
        }

        $value = $data['content'] ?? null;

        if ($type === ContentType::Images) {
            $decoded = json_decode((string) ($value ?? ''), true);

            $data['content_images'] = is_array($decoded) ? array_values($decoded) : [];
        } else {
            $data['content_' . $type->value] = $value;
        }

        return $data;
    }

    /**
     * 虚拟字段映射回 content 列（images 编码为 JSON 路径数组）
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function mapVirtualContentField(array $data, ContentType $type): array
    {
        $value = $data['content_' . $type->value] ?? null;

        $data['content'] = $type === ContentType::Images
            ? (blank($value) ? null : json_encode(array_values($value)))
            : $value;

        unset($data['content_textarea'], $data['content_richtext'], $data['content_markdown'], $data['content_images']);

        return $data;
    }
}
