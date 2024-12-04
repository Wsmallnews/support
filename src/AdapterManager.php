<?php

namespace Wsmallnews\Support;

use ArrayAccess;
use Illuminate\Support\Collection;
use JsonSerializable;
use Wsmallnews\Support\Contracts\AdapterInterface;
use Wsmallnews\Support\Contracts\AdapterManagerInterface;
use Wsmallnews\Support\Exceptions\SupportException;

class AdapterManager implements AdapterManagerInterface
{

    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * 驱动列表
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * 注册的自定义 驱动列表
     *
     * @var array
     */
    protected $customCreators = [];


    public function __construct($app)
    {
        $this->app = $app;
    }



    /**
     * 获取一个 driver 实例
     *
     * @param  string|null  $name
     * @return AdapterInterface
     */
    public function driver($name = null)
    {
        return $this->getAdapter($name);
    }


    /**
     * 获取一个 driver 实例
     *
     * @param  string|null  $name
     * @return AdapterInterface
     */
    public function getAdapter($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->drivers[$name] = $this->get($name);
    }



    /**
     * 尝试从缓存中获取 driver 实例
     *
     * @param  string  $name
     * @return AdapterInterface
     */
    protected function get($name)
    {
        return $this->drivers[$name] ?? $this->resolve($name);
    }

    /**
     * Resolve driver
     *
     * @param  string  $name
     * @param  array|null  $config
     * @return AdapterInterface
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name, $config = null)
    {
        $config ??= $this->getConfig($name);

        if (empty($config['driver'])) {
            throw new SupportException("当前驱动 [{$name}] 为空.");
        }

        $name = $config['driver'];

        if (isset($this->customCreators[$name])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create' . ucfirst($name) . 'Driver';

        if (!method_exists($this, $driverMethod)) {
            throw new SupportException("当前驱动 [{$name}] 不支持.");
        }

        return $this->{$driverMethod}($config);
    }

    /**
     * Call a custom driver creator.
     *
     * @param  array  $config
     * @return Sender
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($config);
    }



    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return '';
    }


    /**
     * Get the filesystem connection configuration.
     *
     * @param  string  $name
     * @return array
     */
    protected function getConfig($name)
    {
        return [];
    }
}
