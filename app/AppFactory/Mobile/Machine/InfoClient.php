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
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Mobile\MobileBase;

class InfoClient extends MobileBase
{
    use MachineTrait,MachineChannelTrait,MachineGoodsTrait;
    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->checkToken();
    }

    public function getInfo()
    {
        return $this->rQ($this->getMachineFind(['machine_id' => $this->tokenArr['machine_id']],'m_id,machine_id,machine_name'));
    }

    public function getChannel()
    {
        return $this->rQ($this->getMachineChannelList(['machine_id' => $this->tokenArr['machine_id']],0,'sku,channel_code,g_name,stock,mc_id,pic'));
    }

    public function getMachineGoods()
    {
        return $this->rQ($this->getMachineGoodsList(['machine_id' => $this->tokenArr['machine_id']],0,"mg_id,g_name,sku,pic,standby_stock"));
    }
}