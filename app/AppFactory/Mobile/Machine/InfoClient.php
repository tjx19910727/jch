<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 17:22
 */

namespace app\AppFactory\Mobile\Machine;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Mobile\MobileBase;

class InfoClient extends MobileBase
{
    use MachineTrait,MachineChannelTrait;
    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->checkToken();
    }
}