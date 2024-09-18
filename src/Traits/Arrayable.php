<?php

namespace Wsmallnews\support\Traits;

use Illuminate\Support\Str;
use ReflectionClass;

trait Arrayable
{
    /**
     * toArray.
     */
    public function toArray(): array
    {
        $result = [];

        foreach ((new ReflectionClass($this))->getProperties() as $item) {
            $k = $item->getName();
            $method = 'get' . Str::studly($k);

            $result[Str::snake($k)] = method_exists($this, $method) ? $this->{$method}() : $this->{$k};
        }

        return $result;
    }
}
