<?php

namespace Wsmallnews\Support;

use ArrayAccess;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * 管道数据总线（参考 yansongda/artful 的 Rocket 模式）：三段式数据载体。
 *
 * - params：调用方传入的原始参数（只读语义，管道不写回）
 * - radars：管道处理过程中的中间态（读写自由）
 * - payloads：阶段产出物（summary/creating 等收尾阶段写入，落单/展示用）
 *
 * 注意：radars 可能持有 Eloquent Model 实例（如 relate_items 中的商品），
 * 队列/缓存场景禁止整体序列化 Rocket——只允许携带标量（如 order id）。
 */
class Rocket implements ArrayAccess, JsonSerializable
{
    /**
     * 传入的数据
     */
    protected array $params = [];

    /**
     * 处理过程中产生的数据
     */
    protected array $radars = [];

    /**
     * 最终处理好的数据
     */
    protected ?Collection $payloads = null;

    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * 获取指定传入参数.
     *
     * @param  string  $name
     * @param  mixed  $default
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        return $this->params[$name] ?? $default;
    }

    public function setParams(array $params): Rocket
    {
        $this->params = $params;

        return $this;
    }

    public function mergeParams(array $params): Rocket
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    public function getRadar($name, $default = null)
    {
        return $this->radars[$name] ?? $default;
    }

    public function getRadars(): array
    {
        return $this->radars;
    }

    public function setRadars(array $radars): Rocket
    {
        $this->radars = $radars;

        return $this;
    }

    public function setRadar($key, $value): Rocket
    {
        data_set($this->radars, $key, $value);

        return $this;
    }

    /**
     * @deprecated version 1.0.0 合并里面的子项数组 (这个方法准备 删掉，这个是往 radar 数组的一个元素合并子数组)
     *
     * @param  string  $field
     */
    public function mergeRadarField(array $value, $field): Rocket
    {
        $current = $this->getRadar($field, []);
        $value = array_merge($current, $value);

        $this->radars = array_merge($this->radars, [
            $field => $value,
        ]);

        return $this;
    }

    public function mergeRadars(array $radars): Rocket
    {
        $this->radars = array_merge($this->radars, $radars);

        return $this;
    }

    public function getPayload($name, $default = null)
    {
        return $this->payloads[$name] ?? $default;
    }

    public function getPayloads(): ?Collection
    {
        return $this->payloads;
    }

    public function setPayloads(array $payloads): Rocket
    {
        $this->payloads = collect($payloads);

        return $this;
    }

    /**
     * 合并payload.
     *
     * @param  array  $payload
     * @return $this
     */
    public function mergePayloads(array $payloads): Rocket
    {
        $this->payloads = $this->payloads
            ? $this->payloads->merge($payloads)
            : collect($payloads);

        return $this;
    }

    /**
     * 数组访问代理到 payloads（管道产物的便捷读取）
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->payloads?->offsetExists($offset) ?? false;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->offsetExists($offset) ? $this->payloads->offsetGet($offset) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->payloads ??= new Collection;
        $this->payloads->offsetSet($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        if ($this->payloads?->offsetExists($offset)) {
            $this->payloads->offsetUnset($offset);
        }
    }

    /**
     * 序列化输出三段数据（调试/日志用）
     */
    public function jsonSerialize(): array
    {
        return [
            'params' => $this->params,
            'radars' => $this->radars,
            'payloads' => $this->payloads,
        ];
    }
}
