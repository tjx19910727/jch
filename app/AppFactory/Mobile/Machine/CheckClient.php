<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 8:41
 */

namespace app\AppFactory\Mobile\Machine;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Goods\GoodsChangeTrait;
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
    use GoodsTrait,GoodsChangeTrait;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $check = $this->checkToken();
        if ($check) die($check);
        $this->manager = $this->getAuthManagerFind(['manager_id' => $this->tokenArr['manager_id']],'manager_id,nickname,account,ao_id');
        if (!$this->manager) {
            die($this->r(100,$this->lang('MachineCheck.manager_no_data')));
        }
        $this->ignoreList = (config("auth_manager_log_list.ignore")['mobile'] ?? []);
        $this->apiUrl = request()->action();
        $this->recordManagerLog($this->manager,3);
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
            $machine = $this->getMachineFind(['m_id' => $postData['m_id']], 'm_id,machine_id,machine_name,ao_id');
            if (!$machine) return $this->r(100, $this->lang("MachineCheck.machine_no_data"));
            $machine = $machine->toArray();
            $checkList = json2arr($postData['checkList']);
            // 库存盘点基础数据
            $insert = [
                "m_id" => $machine['m_id'],
                "machine_id" => $machine['machine_id'],
                "machine_name" => $machine['machine_name'],
                "type" => $postData['type'],
                "ao_id" => $machine['ao_id'],
                "create_date" => strtotime(date("Y-m-d")),
                "creator" => $this->tokenArr['manager_id'],
            ];
            // 商品变化基础数据
            $insertGChange = [
                "m_id" => $machine['m_id'],
                "machine_id" => $machine['machine_id'],
                "machine_name" => $machine['machine_name'],
                "ao_id" => $machine['ao_id'],
            ];
            $insertAll = [];
            foreach ($checkList as $ck => $cv) {
                validate(VMachineCheck::class)->scene("checkList" . $postData['type'])->check($cv);
                $insertCs = array_merge($insert,$cv);
                if ($postData['type'] == 1 && $cv['mc_id'] && $cv['mc_id'] > 0) {
                    $mc = $this->getMachineChannelFind(['mc_id' =>$cv['mc_id']],'mc_id,channel_code,mg_id,g_id,g_name,pic,sku,bar_code,gc_id,gc_id,gc_name,stock system_stock');
                    if (!$mc) {
                        $this->rollbackTrans();
                        return $this->rFail($this->lang("MachineCheck.mc_no_data"));
                    }
                    $mc = $mc->toArray();
                    $bar_code = $mc['bar_code'];
                    unset($mc['bar_code']);

                    $insertCs = array_merge($insertCs,$mc);

                    // 20250604 盘点商品变化，库存盘盈、库存盘亏（货架）
                    $insertGc = array_merge($insertGChange,[
                        "mc_id" => $mc['mc_id'],
                        "channel_code" => $mc['channel_code'],
                        "mg_id" => $mc['mg_id'],
                        "g_id" => $mc['g_id'],
                        "g_name" => $mc['g_name'],
                        "gc_id" => $mc['gc_id'],
                        "gc_name" => $mc['gc_name'],
                        "pic" => $mc['pic'],
                        "sku" => $mc['sku'],
                        "bar_code" => $bar_code,
                        "change_value" => bcsub($cv['check_stock'],$mc['system_stock']),  // 盘点库存-系统库存，商品变化数量
                        "type" => $cv['status'] == 3 ? 5 : 4,  //  3 库存盘亏 5，1、2 库存盘盈 4，
                        "desc" => ($cv['status'] == 3 ? $this->lang("goodsChange.check_stock_shortage") : $this->lang("goodsChange.check_stock_surplus")),
                        "position" => 1,
                    ]);
                    $this->addGoodsChange($insertGc);
                }
                if ($postData['type'] == 2 && $cv['mg_id'] && $cv['mg_id'] > 0) {
                    $mg = $this->getMachineGoodsFind(['mg_id' => $cv['mg_id']], 'mg_id,g_id,g_name,pic,sku,bar_code,gc_id,gc_name, standby_stock system_stock');
                    if (!$mg) {
                        $this->rollbackTrans();
                        return $this->r(100, $this->lang("MachineCheck.mg_no_data"));
                    }
                    $mg = $mg->toArray();
                    $insertCs = array_merge($insertCs,$mg);

                    // 20250604 盘点商品变化，库存盘盈、库存盘亏（设备商品库）
                    $insertGc = array_merge($insertGChange,[
                        "mc_id" => $mc['mc_id'] ?? 0,
                        "channel_code" => $mc['channel_code'] ?? "",
                        "mg_id" => $mg['mg_id'],
                        "g_id" => $mg['g_id'],
                        "g_name" => $mg['g_name'],
                        "gc_id" => $mg['gc_id'],
                        "gc_name" => $mg['gc_name'],
                        "pic" => $mg['pic'],
                        "sku" => $mg['sku'],
                        "bar_code" => $mg['bar_code'],
                        "change_value" => bcsub($cv['check_stock'],$mg['system_stock']),  // 盘点库存-系统库存，商品变化数量
                        "type" => $cv['status'] == 3 ? 5 : 4,   //  3 库存盘亏 5，1、2 库存盘盈 4，
                        "desc" => ($cv['status'] == 3 ? $this->lang("goodsChange.check_stock_shortage") : $this->lang("goodsChange.check_stock_surplus")),
                        "position" => 2,
                    ]);
                    $this->addGoodsChange($insertGc);
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