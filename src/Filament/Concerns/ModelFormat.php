<?php

namespace Wsmallnews\Support\Filament\Concerns;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Wsmallnews\Support\Contracts\HasModelLabel;
use Wsmallnews\Support\Contracts\HasSnSubject;

class ModelFormat
{
    /**
     * Get the title for the subject model.
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
     * Get the URL for the subject model.
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
        return static::resolveModelLabel(static::getModelName($type));
    }

    /**
     * Resolve the actual model class name from a morph type string.
     */
    public static function getModelName(string $type): string
    {
        $model = Str::contains($type, '\\') ? $type : Relation::getMorphedModel($type);
        $model = filled($model) ? $model : $type;

        return $model;
    }

    /**
     * Get the custom URL for the subject model via HasSnSubject.
     */
    public static function customUrl(?Model $model): ?string
    {
        if ($model instanceof HasSnSubject) {
            return $model->getSnSubjectHrefUrl();
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
        if ($model instanceof HasSnSubject) {
            $title = $model->getSnSubjectTitle();

            return filled($title) ? (string) $title : class_basename($model) . ' #' . $model->getKey();
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
     * Resolve human-readable label for a model class.
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
