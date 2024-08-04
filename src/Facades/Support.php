<?php

namespace Wsmallnews\Support\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Wsmallnews\Support\Support
 */
class Support extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Wsmallnews\Support\Support::class;
    }
}
