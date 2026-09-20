<?php

namespace Wsmallnews\Support\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Wsmallnews\Support\Data\ScopeableContext;

/**
 * Helper functions for scope operations.
 */
class ScopeableHelper
{
    /**
     * Create a scope context from various input formats.
     *
     * @param  mixed  $input  Can be array or ScopeableContext
     */
    public static function resolve(mixed $input): ScopeableContext
    {
        if ($input instanceof ScopeableContext) {
            return $input;
        }

        if (is_array($input)) {
            return ScopeableContext::fromArray($input);
        }

        throw new \InvalidArgumentException('Invalid scope input type. Expected array or ScopeableContext.');
    }

    /**
     * Get scope attributes for model creation.
     *
     * @param  ScopeableContext|array  $scope
     * @return array{scope_type: string, scope_id: int}
     */
    public static function toAttributes(mixed $scope): array
    {
        return self::resolve($scope)->toArray();
    }

    /**
     * Apply scope to a query builder.
     *
     * @param  Builder  $query
     * @param  ScopeableContext|array  $scope
     * @return Builder
     */
    public static function applyToQuery($query, mixed $scope)
    {
        $context = self::resolve($scope);

        return $query->scopeable($context->scopeType, $context->scopeId);
    }

    /**
     * Check if a model belongs to a specific scope.
     *
     * @param  Model  $model
     * @param  ScopeableContext|array  $scope
     */
    public static function modelBelongsToScopeable($model, mixed $scope): bool
    {
        $context = self::resolve($scope);

        return $model->scope_type === $context->scopeType
            && $model->scope_id === $context->scopeId;
    }
}
