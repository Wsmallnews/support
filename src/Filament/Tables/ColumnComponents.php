<?php

namespace Wsmallnews\Support\Filament\Tables;

use Closure;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\HtmlString;
use Wsmallnews\Support\Contracts\HasSnIdentifiable;
use Wsmallnews\Support\Enums\ContentType;
use Wsmallnews\Support\Helpers\FilamentModelHelper;

use function Filament\Support\generate_href_html;

class ColumnComponents
{
    public static function contentColumn(
        string $name,
        string $label,
        array | bool $searchable = true,
        ?Closure $actionResolver = null,
    ): TextColumn {
        // 处理 内容 弹框
        $action = Action::make('viewContent')
            ->modal()
            ->modalHeading(fn ($record) => __('sn-support::support.table.column.view_content') . ' #' . $record->id)
            ->modalWidth(Width::FourExtraLarge)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('sn-support::support.table.column.content_close'));
        $action = $actionResolver ? $actionResolver($action) : $action;

        return TextColumn::make($name)
            ->label($label)
            ->state(function ($record) {
                // 文本域内容直接返回，其他类型返回 '-', 保证 formatStateUsing 方法正常被调用(state == null, 会跳过 formatStateUsing 方法)
                return $record->content_type === ContentType::Textarea ? $record->content : '-';
            })
            ->formatStateUsing(function ($state, $record) {
                $html = '<div class="flex max-w-80 items-center gap-1 overflow-hidden">';
                if ($record->content_type === ContentType::Textarea) {
                    $html .= '<div class="w-full truncate">' . $state . '</div>';
                } else {
                    $html .= svg('heroicon-m-document-text', 'w-4 h-4')->toHtml();
                    $html .= e($record->content_type->getLabel());
                }
                $html .= '</div>';

                return new HtmlString($html);
            })
            ->searchable($searchable)
            ->action($action)
            ->toggleable();
    }

    /**
     * 模型列：左侧图片 + 右侧标题/描述，支持点击跳转。
     *
     * @param  string  $name  列名（如 'causer.name'）
     * @param  string  $label  列标签
     * @param  Closure  $modelResolver  从 $record 获取关联模型，如 fn ($record) => $record->causer
     */
    public static function modelColumn(
        string $name,
        string $label,
        Closure $modelResolver,
    ): TextColumn {
        return TextColumn::make($name)
            ->label($label)
            ->formatStateUsing(function ($state, $record) use ($modelResolver): HtmlString {
                $model = $modelResolver($record);

                return static::modelInfo($model, 'model');
            })
            ->disabledClick()
            ->searchable()
            ->sortable()
            ->toggleable();
    }

    /**
     * 关联模型列：左侧图片 + 右侧标题/描述，支持点击跳转。
     *
     * @param  string  $name  列名（如 'causer.name'）
     * @param  string  $label  列标签
     * @param  Closure  $modelResolver  从 $record 获取关联模型，如 fn ($record) => $record->causer
     * @param  ?Closure  $relationTypeResolver  关联模型类型 fn ($record) => ‘post’  或者 fn ($record) => Post::class 
     * @param  ?Closure  $relationIdResolver  从 $record 获取关联 ID，如 fn ($record) => $record->post_id
     */
    public static function relationColumn(
        string $name,
        string $label,
        Closure $modelResolver,
        ?Closure $relationTypeResolver = null,
        ?Closure $relationIdResolver = null,
    ): TextColumn {
        return TextColumn::make($name)
            ->label($label)
            ->state(function ($record) use ($name, $relationTypeResolver) {
                $relationType = $relationTypeResolver instanceof Closure ? $relationTypeResolver($record) : null;
                return $relationType ?? ($record->{$name} ?? '-');       // 确保 formatStateUsing 方法执行(null 的话 formatStateUsing 不会执行)
            })
            ->formatStateUsing(function ($state, $record) use ($modelResolver, $relationTypeResolver, $relationIdResolver): HtmlString {
                $model = $modelResolver($record);
                $relationType = $relationTypeResolver instanceof Closure ? $relationTypeResolver($record) : null;
                $relationId = $relationIdResolver instanceof Closure ? $relationIdResolver($record) : null;

                if ($model) {
                    return static::modelInfo($model, 'relation');
                } else {
                    return static::emptyModelInfo(
                        $relationType,
                        $relationId,
                    );
                }
            })
            ->disabledClick()
            ->searchable()
            ->sortable()
            ->toggleable();
    }

    /**
     * 多态关联模型列：左侧图片 + 右侧标题/描述，支持点击跳转。
     *
     * @param  string  $name  列名（如 'causer.name'）
     * @param  string  $label  列标签
     * @param  Closure  $modelResolver  从 $record 获取关联模型，如 fn ($record) => $record->causer
     * @param  ?Closure  $morphTypeResolver  从 $record 获取多态类型，如 fn ($record) => $record->causer_type
     * @param  ?Closure  $morphIdResolver  从 $record 获取多态 ID，如 fn ($record) => $record->causer_id
     */
    public static function morphColumn(
        string $name,
        string $label,
        Closure $modelResolver,
        ?Closure $morphTypeResolver = null,
        ?Closure $morphIdResolver = null,
    ): TextColumn {
        return TextColumn::make($name)
            ->label($label)
            ->state(function ($record) use ($name, $morphTypeResolver) {
                $morphType = $morphTypeResolver instanceof Closure ? $morphTypeResolver($record) : null;
                return $morphType ?? ($record->{$name} ?? '-');       // 确保 formatStateUsing 方法执行(null 的话 formatStateUsing 不会执行)
            })
            ->formatStateUsing(function ($state, $record) use ($modelResolver, $morphTypeResolver, $morphIdResolver): HtmlString {
                $model = $modelResolver($record);
                $morphType = $morphTypeResolver instanceof Closure ? $morphTypeResolver($record) : null;
                $morphId = $morphIdResolver instanceof Closure ? $morphIdResolver($record) : null;

                if ($model) {
                    return static::modelInfo($model, 'morph');
                } else {
                    return static::emptyModelInfo(
                        $morphType,
                        $morphId,
                    );
                }
            })
            ->disabledClick()
            ->searchable()
            ->sortable()
            ->toggleable();
    }

    /**
     * 处理 模型 样式
     *
     * @param  string  $type
     * @return HtmlString
     */
    protected static function modelInfo(mixed $model, $type = 'model')
    {
        $modelType = $model instanceof HasSnIdentifiable ? 'identifiable' : 'other';

        $coverUrl = FilamentModelHelper::getCoverUrl($model);
        $title = FilamentModelHelper::getTitle($model);
        $description = FilamentModelHelper::getDescription($model);

        $imageClass = $modelType === 'identifiable' ? 'sn-avatar' : 'sn-image';
        $imageHtml = '<div class="' . $imageClass . '">';
        if ($coverUrl) {
            $imageHtml .= '<img class="w-full h-full object-cover" src="' . files_url($coverUrl) . '" alt="' . $title . '" />';
        } else {
            $imageHtml .= '<div class="sn-image-placeholder">
                ' . svg($modelType === 'identifiable' ? 'heroicon-m-user' : 'heroicon-m-photo')->toHtml() . '
            </div>';
        }
        $imageHtml .= '</div>';

        $url = FilamentModelHelper::getUrl($model);
        $tag = in_array($type, ['morph', 'relation']) && filled($url) ? 'a' : 'div';

        $contentHtml = '<div class="flex flex-col justify-between max-w-80">
            <' . $tag . ' ' . ($tag == 'a' ? generate_href_html($url) : '') . ' class="flex items-center gap-1 no-underline">';
        if (in_array($type, ['morph', 'relation'])) {
            $contentHtml .= '<span class="sn-primary-text">#' . $model->getKey() . '</span>';
        }
        if ($type == 'morph') {
            $contentHtml .= '<span class="sn-badge sn-badge-primary">
                        ' . FilamentModelHelper::getModelLabel($model) . '
                    </span>';
        }
        $contentHtml .= '<span class="sn-content-text ' . ($tag == 'a' ? 'sn-hover' : '') . ' truncate" title="' . $title . '">' . $title . '</span>
            </' . $tag . '>
            <span class="text-sm font-medium text-gray-400 dark:text-white truncate" title="' . $description . '">' . $description . '</span>
        </div>';

        return new HtmlString(
            '<div class="flex items-center gap-2 justify-start">' . $imageHtml . $contentHtml . '</div>'
        );
    }

    /**
     * 已删除多态模型的降级显示
     */
    protected static function emptyModelInfo(?string $modelType, ?int $modelId): HtmlString
    {
        if (! $modelType) {
            return new HtmlString('<span class="text-gray-400">-</span>');
        }

        $modelLabel = filled($modelType) ? FilamentModelHelper::getTypeLabel($modelType) : '-';
        $id = $modelId ?? null;

        $imageHtml = '<div class="sn-image">
            <div class="sn-image-placeholder">
                ' . svg('heroicon-m-question-mark-circle')->toHtml() . '
            </div>
        </div>';

        $contentHtml = '<div class="flex flex-col justify-between max-w-80">
            <div class="flex items-center gap-1">';
                $contentHtml .= $id ? '<span class="sn-primary-text">#' . e((string) $id) . '</span>' : '';
                $contentHtml .= '<span class="sn-badge sn-badge-primary">' . e($modelLabel) . '</span>';
                $contentHtml .= $id ? '<span class="sn-content-text sn-danger-text truncate">' . __('sn-support::support.table.column.model_deleted') . '</span>' : '';
            $contentHtml .= '</div>
        </div>';

        return new HtmlString(
            '<div class="flex items-center gap-2 justify-start">' . $imageHtml . $contentHtml . '</div>'
        );
    }
}
