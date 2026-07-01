<?php

namespace Wsmallnews\Support\Helpers;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Wsmallnews\Support\Contracts\HasModelLabel;
use Wsmallnews\Support\Contracts\HasSnIdentifiable;
use Wsmallnews\Support\Contracts\HasSnSubject;

class FilamentModelHelper
{
    /**
     * 获取模型标题/名称。
     */
    public static function getTitle(?Model $model): string | HtmlString
    {
        if (! $model instanceof Model) {
            return (string) ($model ?? '-');
        }

        $title = null;

        if ($model instanceof HasSnSubject) {
            $title = $model->getSnSubjectTitle();
        } elseif ($model instanceof HasSnIdentifiable) {
            $title = $model->getSnName();
        }

        if (blank($title)) {
            foreach (['name', 'title', 'email', 'username', 'label', 'content'] as $attribute) {
                if ($model->hasAttribute($attribute)) {
                    $title = (string) $model->getAttribute($attribute);

                    break;
                }
            }
        }

        return filled($title) ? (string) $title : class_basename($model);
    }

    /**
     * 获取模型详情页 URL。
     */
    public static function getUrl(?Model $model): ?string
    {
        if (! $model instanceof Model) {
            return null;
        }

        $url = null;
        if ($model instanceof HasSnSubject) {
            $url = $model->getSnSubjectHrefUrl();
        } elseif ($model instanceof HasSnIdentifiable) {
            $url = $model->getSnHrefUrl();
        }

        return filled($url) ? (string) $url : static::resolveResourceUrl($model);
    }

    /**
     * 获取模型描述。
     */
    public static function getDescription(?Model $model): ?string
    {
        if (! $model instanceof Model) {
            return null;
        }

        $description = null;
        if ($model instanceof HasSnSubject) {
            $description = $model->getSnSubjectDescription();
        } elseif ($model instanceof HasSnIdentifiable) {
            $description = $model->getSnEmail();
        }

        if (blank($description)) {
            if ($model->hasAttribute('description')) {
                $description = $model->getAttribute('description');
            }
        }

        return $description ?? null;
    }

    /**
     * 获取模型封面/头像 URL。
     *
     * @param ?Model
     */
    public static function getCoverUrl(?Model $model): ?string
    {
        if (! $model instanceof Model) {
            return null;
        }

        $cover = null;
        if ($model instanceof HasSnSubject) {
            $cover = $model->getSnSubjectCoverUrl();
        } elseif ($model instanceof HasSnIdentifiable) {
            $cover = $model->getSnAvatarUrl();
        }

        return $cover ?? null;
    }

    /**
     * Get subject type options for filter/select.
     */
    public static function getTypeOptions(Collection $types): array
    {
        return $types->mapWithKeys(function ($type) {
            return [$type => static::getTypeLabel($type)];
        })->toArray();
    }

    /**
     * Get human-readable label for a morph type.
     */
    public static function getTypeLabel(string $type): string
    {
        return static::getModelLabel(static::getModelClassName($type));
    }

    /**
     * Resolve the actual model class name from a morph type string.
     */
    protected static function getModelClassName(string $type): string
    {
        $modelName = Str::contains($type, '\\') ? $type : Relation::getMorphedModel($type);

        return filled($modelName) ? $modelName : $type;
    }

    /**
     * Resolve human-readable label for a model class.
     */
    public static function getModelLabel(mixed $model): string
    {
        if (is_subclass_of($model, HasModelLabel::class)) {
            return $model::getModelLabel();
        }

        if (method_exists($model, 'getModelLabel')) {
            return $model::getModelLabel();
        }

        return class_basename($model);
    }

    /**
     * Resolve Filament Resource URL for a model.
     */
    protected static function resolveResourceUrl(Model $model): ?string
    {
        $resource = Filament::getModelResource(get_class($model));

        if ($resource && $resource::hasPage('view')) {
            return $resource::getUrl('view', ['record' => $model]);
        }

        if ($resource && $resource::hasPage('edit')) {
            return $resource::getUrl('edit', ['record' => $model]);
        }

        return null;
    }
}
