<?php

namespace Wsmallnews\Support;

use ArrayAccess;
use Illuminate\Support\Collection;
use JsonSerializable;
use Wsmallnews\Support\Traits\{
    Accessable,
    Arrayable,
    Serializable
};

class Rocket implements JsonSerializable, ArrayAccess
{
    use Accessable;
    use Arrayable;
    use Serializable;

    /**
     * 传入的数据
     *
     * @var array
     */
    protected array $params = [];


    /**
     * 处理过程中产生的数据
     *
     * @var array
     */
    protected array $radars = [];



    /**
     * 最终处理好的数据
     *
     * @var Collection|null
     */
    protected ?Collection $payloads = null;


    public function getParams(): array
    {
        return $this->params;
    }


    /**
     * 获取指定传入参数.
     *
     * @param string $name
     * @param mixed $default
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


    /**
     * 合并里面的子项数组
     *
     * @param array $value
     * @param string $field
     * @return Rocket
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

    public function setPayloads(?Collection $payloads): Rocket
    {
        $this->payloads = $payloads;

        return $this;
    }


    /**
     * 合并payload.
     *
     * @param array $payload
     *
     * @return $this
     */
    public function mergePayloads(array $payloads): Rocket
    {
        if (empty($this->payloads)) {
            $this->payloads = new Collection();
        }

        $this->payloads = $this->payloads->merge($payloads);

        return $this;
    }
}
