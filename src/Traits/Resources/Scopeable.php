<?php

namespace Wsmallnews\Support\Traits\Resources;

use Illuminate\Database\Eloquent\Builder;

trait Scopeable
{

    protected static string $scope_type = 'default';

    protected static int $scope_id = 0;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->scopeable(self::$scope_type, self::$scope_id);
    }


    
}
