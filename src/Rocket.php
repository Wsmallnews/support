<?php

namespace Wsmallnews\Support;

use ArrayAccess;
use Illuminate\Support\Collection;
use JsonSerializable;

/**
 * Three-stage pipeline payload: params (read-only input), radars (mutable intermediates) and
 * payloads (final results). Never serialise a whole Rocket: radars may hold Eloquent models.
 */
class Rocket implements ArrayAccess, JsonSerializable
{
    /** Input data. */
    protected array $params = [];

    /** Intermediate values. */
    protected array $radars = [];

    /** Final results. */
    protected ?Collection $payloads = null;

    public function getParams(): array
    {
        return $this->params;
    }

    /**
    * Read an input parameter.
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
    * @deprecated 1.0.0 Merges a sub-array into one radar entry; scheduled for removal.
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
    * Merge into the payload.
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

    /** Array access proxies to payloads. */
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

    /** Serialise all three stages for debugging or logging. */
    public function jsonSerialize(): array
    {
        return [
            'params' => $this->params,
            'radars' => $this->radars,
            'payloads' => $this->payloads,
        ];
    }
}
