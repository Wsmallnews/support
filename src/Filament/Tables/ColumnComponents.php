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
        // Content modal, configured by the caller via actionResolver (the content may not come from the record)
        $action = Action::make('viewContent')
            ->modal()
            ->modalHeading(fn ($record) => __('sn-support::support.table.column.view_content') . ' #' . $record->id)
            ->modalWidth(Width::FourExtraLarge)
            ->stickyModalHeader()
            ->stickyModalFooter()
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('sn-support::support.table.column.content_close'));
        $action = $actionResolver ? $actionResolver($action) : $action;

        return TextColumn::make($name)
            ->label($label)
            ->state(function ($record) {
                // Plain text returns as-is; other types return '-' so formatStateUsing still runs
                return $record->content_type === ContentType::Textarea ? $record->content : '-';
            })
            ->formatStateUsing(function ($state, $record) {
                $html = '<div class="flex max-w-80 items-center gap-1 overflow-hidden">';
                if ($record->content_type === ContentType::Textarea) {
                    $html .= '<div class="w-full truncate">' . $state . '</div>';
                } else {
                    $html .= $record->content_type === ContentType::Images
                        ? svg('heroicon-m-photo', 'w-4 h-4')->toHtml()
                        : svg('heroicon-m-document-text', 'w-4 h-4')->toHtml();
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
    * Model column: image on the left, title and description on the right, optionally linked.
    *
    * @param  string  $name  Column name, e.g. causer.name
    * @param  string  $label  Column label
    * @param  Closure  $modelResolver  Resolves the model from the record, e.g. fn ($record) => $record->causer
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
            ->searchable()
            ->sortable()
            ->toggleable();
    }

    /**
    * Related model column with an optional type badge.
    *
    * @param  string  $name  Column name, e.g. causer.name
    * @param  string  $label  Column label
    * @param  Closure  $modelResolver  Resolves the model from the record, e.g. fn ($record) => $record->causer
    * @param  ?Closure  $relationTypeResolver  Relation type, e.g. fn ($record) => 'post'
    * @param  ?Closure  $relationIdResolver  Relation id, e.g. fn ($record) => $record->post_id
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

                return $relationType ?? ($record->{$name} ?? '-');       // Ensure formatStateUsing runs; a null value would skip it
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
    * Polymorphic model column.
    *
    * @param  string  $name  Column name, e.g. causer.name
    * @param  string  $label  Column label
    * @param  Closure  $modelResolver  Resolves the model from the record, e.g. fn ($record) => $record->causer
    * @param  ?Closure  $morphTypeResolver  Morph type, e.g. fn ($record) => $record->causer_type
    * @param  ?Closure  $morphIdResolver  Morph id, e.g. fn ($record) => $record->causer_id
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

                return $morphType ?? ($record->{$name} ?? '-');       // Ensure formatStateUsing runs; a null value would skip it
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

    /** Shared model presentation. */
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

    /** Fallback label for a deleted polymorphic model. */
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
