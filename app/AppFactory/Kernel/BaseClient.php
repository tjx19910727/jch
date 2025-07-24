<?php


namespace app\AppFactory\Kernel;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerLogTrait;
use app\AppFactory\Kernel\Traits\CacheTrait;
use app\AppFactory\Kernel\Traits\CommonTrait;
use app\AppFactory\Kernel\Traits\Config\ConfigTrait;
use app\AppFactory\Kernel\Traits\CurlTrait;
use app\AppFactory\Kernel\Traits\DbTrait;
use app\AppFactory\Kernel\Traits\ReturnTrait;


class BaseClient
{
    use DbTrait, CacheTrait, ReturnTrait, CommonTrait, ConfigTrait, CurlTrait;
    use AuthManagerLogTrait;
    protected $app;
    protected $config;
    protected $host;

    public function __construct(ServiceContainer $app)
    {
        $this->app = $app;
        $this->config = $app->getConfig();
        $this->host = env("app.host");
    }
}


