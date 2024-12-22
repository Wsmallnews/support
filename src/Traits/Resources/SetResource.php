<?php

namespace Wsmallnews\support\Traits\Resources;

trait SetResource
{
    public static function setAttribute($key, $value)
    {
        self::$$key = $value;
    }
}
