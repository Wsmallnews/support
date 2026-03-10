<?php

namespace Wsmallnews\Support\Helpers;

use Wsmallnews\Support\Data\ScopeableContext;

/**
 * Helper functions for scope operations.
 */
class ScopeableHelper
{
    /**
     * Create a scope context from various input formats.
     *
     * @param  mixed  $input  Can be array, ScopeableContext, or config key string
     * @return ScopeableContext
     */
    public static function resolve(mixed $input): ScopeableContext
    {
        if ($input instanceof ScopeableContext) {
            return $input;
        }

        if (is_array($input)) {
            return ScopeableContext::fromArray($input);
        }

        if (is_string($input)) {
            return ScopeableContext::fromConfig($input);
        }

        throw new \InvalidArgumentException('Invalid scope input type. Expected array, ScopeableContext, or config key string.');
    }

    /**
     * Get scope attributes for model creation.
     *
     * @param  ScopeableContext|array|string  $scope
     * @return array{scope_type: string, scope_id: int}
     */
    public static function toAttributes(mixed $scope): array
    {
        return self::resolve($scope)->toArray();
    }

    /**
     * Apply scope to a query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  ScopeableContext|array|string  $scope
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function applyToQuery($query, mixed $scope)
    {
        $context = self::resolve($scope);

        return $query->scopeable($context->scopeType, $context->scopeId);
    }

    /**
     * Check if a model belongs to a specific scope.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  ScopeableContext|array|string  $scope
     * @return bool
     */
    public static function modelBelongsToScopeable($model, mixed $scope): bool
    {
        $context = self::resolve($scope);

        return $model->scope_type === $context->scopeType
            && $model->scope_id === $context->scopeId;
    }
}
