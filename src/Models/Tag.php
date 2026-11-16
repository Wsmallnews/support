<?php

namespace Wsmallnews\Support\Models;

use Spatie\Tags\Tag as BaseTagModel;
use Wsmallnews\Support\Contracts\HasModelLabel;
use Wsmallnews\Support\Models\Concerns\SiteScope;

class Tag extends BaseTagModel implements HasModelLabel
{
    use SiteScope;

    /** Default model label. */
    public static function getModelLabel(): string
    {
        return class_basename(static::class);
    }
}
