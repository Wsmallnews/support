<?php

namespace Wsmallnews\Support\Casts;

use ArrayAccess;
use Countable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use Livewire\Wireable;

class CounterCast implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        $data = $value ? json_decode($value, true) : [];

        // Wrapper so missing keys default to 0
        return new class($data) implements Arrayable, ArrayAccess, Countable, Jsonable, JsonSerializable, Wireable
        {
            public function __construct(private array $data) {}

            // ArrayAccess support
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

            public function toLivewire()
            {
                return $this->data;
            }

            public static function fromLivewire($value)
            {
                return new self((array) $value);
            }

            /** Arrayable implementation. */
            public function toArray()
            {
                return $this->data;
            }

            /**
             * Jsonable implementation.
             *
             * @param  int  $options
             * @return string
             */
            public function toJson($options = 0)
            {
                return json_encode($this->jsonSerialize(), $options);
            }

            /** JsonSerializable implementation. */
            public function jsonSerialize(): mixed
            {
                return $this->data;
            }

            /** Countable implementation. */
            public function count(): int
            {
                return count($this->data);
            }
        };
    }

    public function set($model, $key, $value, $attributes)
    {
        return filled($value) ? json_encode($value) : null;
    }
}
