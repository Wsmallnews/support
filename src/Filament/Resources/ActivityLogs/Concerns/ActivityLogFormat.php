<?php

namespace Wsmallnews\Support\Filament\Resources\ActivityLogs\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Wsmallnews\Support\Contracts\ActivityLogs\HasActivityLogTitle;
use Wsmallnews\Support\Contracts\ActivityLogs\HasActivityLogUrl;
use Wsmallnews\Support\Contracts\HasModelLabel;

class ActivityLogFormat
{
    /**
     * Get the title for the model's activity.
     */
    public static function getTitle(mixed $model): string
    {
        if (! $model instanceof Model) {
            return (string) ($model ?? '-');
        }

        $title = static::resolveTitle($model);

        // Nested resource support: if the model has a parent defined, prepend it
        if (method_exists($model, 'getActivityLogParent') && ($parent = $model->getActivityLogParent()) instanceof Model) {
            $title = static::resolveTitle($parent) . ' > ' . $title;
        }

        return $title;
    }

    /**
     * Get the URL for the model's activity.
     */
    public static function getUrl(?Model $model): ?string
    {
        if (! $model instanceof Model) {
            return null;
        }

        if ($customUrl = static::customUrl($model)) {
            return $customUrl;
        }

        $resource = Filament::getModelResource(get_class($model));

        if ($resource && $resource::hasPage('view')) {
            return $resource::getUrl('view', ['record' => $model]);
        }
        if ($resource && $resource::hasPage('edit')) {
            return $resource::getUrl('edit', ['record' => $model]);
        }

        return null;
    }

    /**
     * 获取 subject type options
     *
     * @return array
     */
    public static function getSubjectTypeOptions(Collection $subjectTypes)
    {
        $options = $subjectTypes->mapWithKeys(function ($type) {
            $model = Str::contains($type, '\\') ? $type : Relation::getMorphedModel($type);
            $model = filled($model) ? $model : $type;       // 防止误判

            $label = static::resolveModelLabel($model);

            return [$type => $label];
        })->toArray();

        return $options;
    }

    /**
     * Get the custom URL for the model's activity.
     */
    public static function customUrl(?Model $model): ?string
    {
        if ($model instanceof HasActivityLogUrl) {
            return $model->getActivityLogUrl();
        }

        if (method_exists($model, 'getActivityLogUrl')) {
            return $model->getActivityLogUrl();
        }

        return null;
    }

    /**
     * Resolve the base title for a model.
     */
    protected static function resolveTitle(Model $model): string
    {
        if ($model instanceof HasActivityLogTitle) {
            return $model->getActivityLogTitle();
        }

        if (method_exists($model, 'getActivityLogTitle')) {
            return $model->getActivityLogTitle();
        }

        foreach (['name', 'title', 'email', 'username', 'label'] as $attribute) {
            if ($model->hasAttribute($attribute)) {
                return (string) $model->getAttribute($attribute);
            }
        }

        return class_basename($model) . ' #' . $model->getKey();
    }

    /**
     * 模型 label
     */
    protected static function resolveModelLabel(string $model): string
    {
        if (is_subclass_of($model, HasModelLabel::class)) {
            return $model::getModelLabel();
        }

        if (method_exists($model, 'getModelLabel')) {
            return $model::getModelLabel();
        }

        return class_basename($model);
    }
}
