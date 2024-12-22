<?php

namespace Wsmallnews\Support\Traits\Resources;

trait SetResource
{
    public static function setAttribute($key, $value)
    {
        self::$$key = $value;
    }
}
