<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/5/4
 * Time: 15:49
 */

namespace InConnect\Kernel;

class ServiceContainer extends Container
{

    protected $providers = [];

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @var array
     */
    protected $defaultConfig = [];

    /**
     * ServiceContainer constructor.
     * @param array $config
     */
    public function __construct(array $config = [], array $prepends = [])
    {
        $this->registerProviders($this->getProvider());
        parent::__construct($prepends);
        $this->config = $config;
    }


    public function getProvider()
    {
        return array_merge([
        ], $this->providers);
    }

    /**
     * 获取配置信息
     * @return array
     */
    public function getConfig()
    {
//        return $this->machineInfo;
        return get_object_vars($this)['config'];
    }

    /**
     * @param string $id
     * @param mixed  $value
     */
    public function rebind($id, $value)
    {
        $this->offsetUnset($id);
        $this->offsetSet($id, $value);
    }

    /**
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public function __get($id)
    {
        return $this->offsetGet($id);
    }

    public function __set($id, $value)
    {
        $this->offsetSet($id, $value);
    }

    public function registerProviders(array $providers)
    {
        foreach ($providers as $provider) {
            parent::register(new $provider());
        }
    }
}