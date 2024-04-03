<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 8:41
 */

namespace app\AppFactory\Mobile\Machine;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineCheckStockTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Mobile\MobileBase;

class CheckClient extends MobileBase
{
    use MachineTrait,MachineChannelTrait,MachineGoodsTrait,MachineCheckStockTrait;
    use GoodsTrait;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->checkToken();
    }

    /**
     * 库存盘点
     * @param $postData
     * @return array|string
     */
    public function channelStock($postData)
    {
        $machine = $this->getMachineFind(['m_id' => $postData['m_id']],'m_id,machine_id,machine_name');
        if (!$machine) return $this->r(100,$this->lang("channelStock.machine_no_data"));
        $machine = $machine->toArray();
        $mg = $this->getMachineGoodsFind(['g_id' => $mc['g_id'],'m_id' => $machine['m_id']],'mg_id,g_id,g_name,pic,sku,bar_code,gc_id,gc_name, standby_stock system_stock');
        if (!$machine) return $this->r(100,$this->lang("channelStock.mg_no_data"));
        $mg = $mg->toArray();
        $data = array_merge($postData,$machine,$mg);
        if (isset($postData['mc_id'])) {
            $mc = $this->getMachineChannelFind(['mc_id' => $postData['mc_id']], 'mc_id,channel_code,stock system_stock');
            if ($mc) {
                $mc = $mc->toArray();
                $data = array_merge($data, $mc);
            }
        }
        $data['create_date'] = strtotime(date("Y-m-d"));
        $data['creator'] = $this->tokenArr['manager_id'];
        return $this->rAction($this->addMachineCheckStock($data));
    }

}