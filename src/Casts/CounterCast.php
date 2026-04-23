<?php

namespace Wsmallnews\Support\Casts;

use ArrayAccess;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class CounterCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        $data = $value ? json_decode($value, true) : [];

        // 包装为支持默认值
        return new class($data) implements ArrayAccess
        {
            public function __construct(private array $data) {}

            // 实现 ArrayAccess
            public function offsetExists($offset): bool
            {
                return array_key_exists($offset, $this->data);
            }

            public function offsetGet($offset): mixed
            {
                return $this->data[$offset] ?? 0;
            }

            public function offsetSet($offset, $value): void
            {
                $this->data[$offset] = $value;
            }

            public function offsetUnset($offset): void
            {
                unset($this->data[$offset]);
            }

            public function __set($key, $value)
            {
                $this->offsetSet($key, $value);
            }

            public function __get($key)
            {
                return $this->offsetGet($key);
            }

            public function __isset($key)
            {
                return $this->offsetExists($key);
            }
        };
    }

    public function set($model, $key, $value, $attributes)
    {
        return $value ? json_encode($value) : null;
    }
}
