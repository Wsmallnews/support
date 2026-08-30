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
     * 获取模型详情页 URL（统一走后台资源链接兜底）。
     */
    public static function getUrl(?Model $model): ?string
    {
        if (! $model instanceof Model) {
            return null;
        }

        return static::resolveResourceUrl($model);
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
    public static function getModelClassName(string $type): string
    {
        $modelName = Str::contains($type, '\\') ? $type : Relation::getMorphedModel($type);

        return filled($modelName) ? $modelName : $type;
    }

    /**
     * 解析模型的搜索字段（关键词搜索用）。
     *
     * 优先级：
     *  1. Model::getKeywordSearchFields() 静态方法
     *  2. Model::$keywordSearchFields 静态属性
     *  3. 兜底：[$model->getKeyName()]
     *
     * 用于：
     *  - morphFilter 关键词 LIKE 搜索
     *  - CanBeConfigured::getGloballySearchableAttributes()
     */
    public static function resolveKeywordSearchFields(string $modelClass): array
    {
        if (! class_exists($modelClass)) {
            return [];
        }

        // 优先调用静态方法 getKeywordSearchFields()
        if (method_exists($modelClass, 'getKeywordSearchFields')) {
            return $modelClass::getKeywordSearchFields();
        }

        // 其次读取静态属性 $keywordSearchFields
        if (property_exists($modelClass, 'keywordSearchFields')) {
            $fields = $modelClass::$keywordSearchFields;
            if (is_array($fields) && ! empty($fields)) {
                return $fields;
            }
        }

        // 兜底：使用主键
        return [(new $modelClass)->getKeyName()];
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

        // Fallback: getModelResource() 仅查找纯字符串注册的资源，
        // ResourceConfiguration 注册的资源需要从 getResourceConfigurations() 中查找
        if (! $resource) {
            // 查找当前面板的资源配置（无 panel 请求上下文时回退默认面板）
            $panel = Filament::getCurrentOrDefaultPanel();
            foreach ($panel->getResourceConfigurations() as $config) {
                if (is_a($model, $config->resource::getModel())) {
                    $resource = $config->resource;

                    break;
                }
            }
        }

        if ($resource && $resource::hasPage('view')) {
            return $resource::getUrl('view', ['record' => $model]);
        }

        if ($resource && $resource::hasPage('edit')) {
            return $resource::getUrl('edit', ['record' => $model]);
        }

        return null;
    }
}
