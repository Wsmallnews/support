<?php

namespace Wsmallnews\Support\Traits;

use Illuminate\Database\ClassMorphViolationException;

trait Morphable
{
    /**
     * buyable 的 type
     */
    public function morphType(): string
    {
        try {
            return $this->getMorphClass();
        } catch (ClassMorphViolationException $e) {
            return static::class;
        }
    }

    /**
     * buyable 的 id
     */
    public function morphId(): int
    {
        return $this->getKey();
    }
}
