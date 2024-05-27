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
use app\mobile\validate\Machine\VMachineCheck;

class CheckClient extends MobileBase
{
    use MachineTrait, MachineChannelTrait, MachineGoodsTrait, MachineCheckStockTrait;
    use GoodsTrait;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);

        $this->manager = $this->getAuthManagerFind(['manager_id' => $this->tokenArr['manager_id']],'manager_id,nickname,account,ao_id');
        if (!$this->manager) {
            die($this->r(100,$this->lang('MachineCheck.manager_no_data')));
        }
        $this->ignoreList = (config("auth_manager_log_list.ignore")['mobile'] ?? []);
        $this->apiUrl = request()->action();
        $this->recordManagerLog($this->manager);
    }

    /**
     * 库存盘点
     * @param $postData
     * @return array|string
     */
    public function channelStock($postData)
    {
        $this->startTrans();
        try {
            $check = $this->checkToken();
            if ($check) return $check;
            $machine = $this->getMachineFind(['m_id' => $postData['m_id']], 'm_id,machine_id,machine_name');
            if (!$machine) return $this->r(100, $this->lang("MachineCheck.machine_no_data"));
            $machine = $machine->toArray();
            $checkList = json2arr($postData['checkList']);
            $insert = [
                "m_id" => $machine['m_id'],
                "machine_id" => $machine['machine_id'],
                "machine_name" => $machine['machine_name'],
                "type" => $postData['type'],
                "create_date" => strtotime(date("Y-m-d")),
                "creator" => $this->tokenArr['manager_id'],
            ];
            $insertAll = [];
            foreach ($checkList as $ck => $cv) {
                validate(VMachineCheck::class)->scene("checkList" . $postData['type'])->check($cv);
                $insertCs = array_merge($insert,$cv);
                if ($postData['type'] == 1 && $cv['mc_id'] && $cv['mc_id'] > 0) {
                    $mc = $this->getMachineChannelFind(['mc_id' =>$cv['mc_id']],'mc_id,channel_code,mg_id,g_id,g_name,pic,sku,gc_id,gc_id,gc_name,stock system_stock');
                    if (!$mc) {
                        $this->rollbackTrans();
                        return $this->rFail($this->lang("MachineCheck.mc_no_data"));
                    }
                    $mc = $mc->toArray();
                    $insertCs = array_merge($insertCs,$mc);
                }
                if ($postData['type'] == 2 && $cv['mg_id'] && $cv['mg_id'] > 0) {
                    $mg = $this->getMachineGoodsFind(['mg_id' => $cv['mg_id']], 'mg_id,g_id,g_name,pic,sku,bar_code,gc_id,gc_name, standby_stock system_stock');
                    if (!$mg) {
                        $this->rollbackTrans();
                        return $this->r(100, $this->lang("MachineCheck.mg_no_data"));
                    }
                    $mg = $mg->toArray();
                    $insertCs = array_merge($insertCs,$mg);
                }
                $insertAll[] = $insertCs;
            }
            $result = $this->addMachineCheckStockMore($insertAll);
            return $this->checkTrans($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }

}