<?php

namespace Wsmallnews\Support\Helpers;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Wsmallnews\Support\Contracts\HasModelLabel;
use Wsmallnews\Support\Contracts\HasSnSubject;

class FilamentModelHelper
{
    /**
     * Get the title for the subject model.
     * 
     * @param mixed $model
     * @return string | HtmlString
     */
    public static function getTitle(mixed $model): string | HtmlString
    {
        if (! $model instanceof Model) {
            return (string) ($model ?? '-');
        }

        $title = static::resolveTitle($model);

        // Nested resource support: if the model has a parent defined, prepend it
        // if (method_exists($model, 'getActivityLogParent') && ($parent = $model->getActivityLogParent()) instanceof Model) {
        //     $title = static::resolveTitle($parent) . ' > ' . $title;
        // }

        return $title;
    }


    /**
     * Get the URL for the subject model.
     * 
     * @param Model $model
     * @return ?string
     */
    public static function getUrl(?Model $model): ?string
    {
        if (! $model instanceof Model) {
            return null;
        }

        if ($customUrl = static::customUrl($model)) {
            return $customUrl;
        }

        if ($resourceUrl = static::resolveResourceUrl($model)) {
            return $resourceUrl;
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
        return static::resolveModelLabel(static::getModelClassName($type));
    }

    /**
     * Resolve the actual model class name from a morph type string.
     */
    protected static function getModelClassName(string $type): string
    {
        $model = Str::contains($type, '\\') ? $type : Relation::getMorphedModel($type);
        $model = filled($model) ? $model : $type;

        return $model;
    }


    /**
     * Resolve the base title for a model.
     * 
     * @param Model $model
     * @return string | HtmlString
     */
    protected static function resolveTitle(Model $model): string | HtmlString
    {
        $title = null;

        if ($model instanceof HasSnSubject) {
            $title = $model->getSnSubjectTitle();
        }

        if (blank($title)) {
            foreach (['name', 'title', 'email', 'username', 'label', 'content'] as $attribute) {
                if ($model->hasAttribute($attribute)) {
                    $title = (string) $model->getAttribute($attribute);
                    break;
                }
            }
        }
        
        $title = filled($title) ? (string) $title : class_basename($model);

        return new HtmlString('<span class="sn-primary-text">#' . $model->getKey() . '</span> ' . $title);
    }


    /**
     * Get the custom URL for the subject model via HasSnSubject.
     * 
     * @param Model $model
     * @return string|null
     */
    public static function customUrl(?Model $model): ?string
    {
        if ($model instanceof HasSnSubject) {
            return $model->getSnSubjectHrefUrl();
        }

        return null;
    }


    /**
     * Resolve the resource URL for the subject model.
     * 
     * @param Model $model
     * @return string|null
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


    /**
     * Resolve human-readable label for a model class.
     * 
     * @param string $model
     * @return string
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
