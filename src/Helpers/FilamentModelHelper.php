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
    /** Title or name of the model. */
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

    /** Detail page URL, falling back to the panel resource link. */
    public static function getUrl(?Model $model): ?string
    {
        if (! $model instanceof Model) {
            return null;
        }

        return static::resolveResourceUrl($model);
    }

    /** Description of the model. */
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
     * Cover or avatar URL of the model.
     *
     * @param  ?Model
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

    /** Get subject type options for filter/select. */
    public static function getTypeOptions(Collection $types): array
    {
        return $types->mapWithKeys(function ($type) {
            return [$type => static::getTypeLabel($type)];
        })->toArray();
    }

    /** Get human-readable label for a morph type. */
    public static function getTypeLabel(string $type): string
    {
        return static::getModelLabel(static::getModelClassName($type));
    }

    /** Resolve the actual model class name from a morph type string. */
    public static function getModelClassName(string $type): string
    {
        $modelName = Str::contains($type, '\\') ? $type : Relation::getMorphedModel($type);

        return filled($modelName) ? $modelName : $type;
    }

    /**
     * Keyword search fields of a model, in order: the getKeywordSearchFields() static method,
     * the $keywordSearchFields static property, then the primary key.
     */
    public static function resolveKeywordSearchFields(string $modelClass): array
    {
        if (! class_exists($modelClass)) {
            return [];
        }

        // Static method first
        if (method_exists($modelClass, 'getKeywordSearchFields')) {
            return $modelClass::getKeywordSearchFields();
        }

        // Then the static property
        if (property_exists($modelClass, 'keywordSearchFields')) {
            $fields = $modelClass::$keywordSearchFields;
            if (is_array($fields) && ! empty($fields)) {
                return $fields;
            }
        }

        // Fall back to the primary key
        return [(new $modelClass)->getKeyName()];
    }

    /** Resolve human-readable label for a model class. */
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

    /** Resolve Filament Resource URL for a model. */
    protected static function resolveResourceUrl(Model $model): ?string
    {
        $resource = Filament::getModelResource(get_class($model));

        // Fallback: getModelResource() only finds plain string registrations, while resources
        // registered through ResourceConfiguration live in getResourceConfigurations()
        if (! $resource) {
            // Look in the current panel, falling back to the default panel outside a panel request
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
