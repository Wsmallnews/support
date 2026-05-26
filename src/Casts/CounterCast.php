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

        // 包装为支持默认值
        return new class($data) implements Arrayable, ArrayAccess, Countable, Jsonable, JsonSerializable, Wireable
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

            public function toLivewire()
            {
                return $this->data;
            }

            public static function fromLivewire($value)
            {
                return new self((array) $value);
            }

            /**
             * 实现 arrayable 接口
             */
            public function toArray()
            {
                return $this->data;
            }

            /**
             * 实现 Jsonable 接口
             *
             * @param  int  $options
             * @return string
             */
            public function toJson($options = 0)
            {
                return json_encode($this->jsonSerialize(), $options);
            }

            /**
             * 实现 JsonSerializable 接口的 jsonSerialize 方法, 定义了对象在 JSON 序列化时的数据
             */
            public function jsonSerialize(): mixed
            {
                return $this->data;
            }

            /**
             * 实现 Countable 接口的 count 方法, 定义了对象的元素数量
             */
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
