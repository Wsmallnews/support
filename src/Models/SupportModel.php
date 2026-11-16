<?php

namespace Wsmallnews\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Wsmallnews\Support\Contracts\HasModelLabel;
use Wsmallnews\Support\Models\Concerns\SiteScope;

/**
 * Base model for all support package models.
 * Provides scopeable and tenant-aware functionality.
 */
class SupportModel extends Model implements HasModelLabel
{
    use SiteScope;

    /** Default model label. */
    public static function getModelLabel(): string
    {
        return class_basename(static::class);
    }
}
