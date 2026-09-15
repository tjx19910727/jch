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
     * 统一提交货道库存和备用商品库存盘点。
     * channelList为货道库存，machineGoodsList为备用库存。
     * @param array $postData
     * @return array|string
     */
    public function newStock($postData)
    {
        $this->startTrans();
        try {
            $machine = $this->getMachineFind(
                ['machine_id' => $this->tokenArr['machine_id']],
                'm_id,machine_id,machine_name,ao_id'
            );
            if (!$machine) {
                $this->rollbackTrans();
                return $this->r(100, $this->lang("MachineCheck.machine_no_data"));
            }
            $machine = $machine->toArray();
            $channelList = json2arr($postData['channelList'] ?? []);
            $machineGoodsList = json2arr($postData['machineGoodsList'] ?? []);
            if (!is_array($channelList) || !is_array($machineGoodsList)) {
                $this->rollbackTrans();
                return $this->rValidate($this->lang("MachineCheck.checkList_require"));
            }
            if (!$channelList && !$machineGoodsList) {
                return $this->checkTrans(true);
            }

            $checkList = [];
            foreach ($channelList as $channel) {
                if (!is_array($channel)) {
                    throw new \InvalidArgumentException($this->lang("MachineCheck.checkList_require"));
                }
                $checkList[] = array_merge($channel, ["type" => 1]);
            }
            foreach ($machineGoodsList as $machineGoods) {
                if (!is_array($machineGoods)) {
                    throw new \InvalidArgumentException($this->lang("MachineCheck.checkList_require"));
                }
                $checkList[] = array_merge($machineGoods, ["type" => 2]);
            }

            $checkBatchNo = 'CS' . date('YmdHis') . '-' . $machine['m_id'] . '-' . bin2hex(random_bytes(4));
            $insert = [
                "m_id" => $machine['m_id'],
                "machine_id" => $machine['machine_id'],
                "machine_name" => $machine['machine_name'],
                "check_batch_no" => $checkBatchNo,
                "ao_id" => $machine['ao_id'],
                "create_date" => strtotime(date("Y-m-d")),
                "creator" => $this->tokenArr['manager_id'],
            ];
            // 盘点只登记观察值；未实际修改库存时不生成商品变化流水。
            $insertAll = [];

            foreach ($checkList as $cv) {
                $type = intval($cv['type']);
                validate(VMachineCheck::class)->scene("checkList" . $type)->check($cv);
                // 盘点明细只接收允许字段，避免请求覆盖设备、批次和审计字段。
                $insertCs = array_merge($insert, [
                    "type" => $type,
                    "check_stock" => intval($cv['check_stock']),
                ]);

                if ($type === 1) {
                    $mc = $this->getMachineChannelFind(
                        ['mc_id' => $cv['mc_id'], 'm_id' => $machine['m_id']],
                        'mc_id,channel_code,mg_id,g_id,g_name,pic,sku,bar_code,gc_id,gc_id,gc_name,stock system_stock'
                    );
                    if (!$mc) {
                        $this->rollbackTrans();
                        return $this->rFail($this->lang("MachineCheck.mc_no_data"));
                    }
                    $mc = $mc->toArray();
                    unset($mc['bar_code']);
                    $insertCs['status'] = $this->resolveCheckStockStatus($cv['check_stock'], $mc['system_stock']);
                    $insertCs = array_merge($insertCs, $mc);
                }

                if ($type === 2) {
                    $mg = $this->getMachineGoodsFind(
                        ['mg_id' => $cv['mg_id'], 'm_id' => $machine['m_id']],
                        'mg_id,g_id,g_name,pic,sku,bar_code,gc_id,gc_name,standby_stock system_stock'
                    );
                    if (!$mg) {
                        $this->rollbackTrans();
                        return $this->r(100, $this->lang("MachineCheck.mg_no_data"));
                    }
                    $mg = $mg->toArray();
                    $insertCs['status'] = $this->resolveCheckStockStatus($cv['check_stock'], $mg['system_stock']);
                    $insertCs = array_merge($insertCs, [
                        "mc_id" => 0,
                        "channel_code" => "",
                    ], $mg);
                }
                $insertAll[] = $insertCs;
            }

            $result = $this->addMachineCheckStockMore($insertAll);
            return $this->checkTrans($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
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
            // 同一次盘点请求的所有明细共用一个批次号，供后台按次汇总。
            $checkBatchNo = 'CS' . date('YmdHis') . '-' . $machine['m_id'] . '-' . bin2hex(random_bytes(4));
            // 库存盘点基础数据
            $insert = [
                "m_id" => $machine['m_id'],
                "machine_id" => $machine['machine_id'],
                "machine_name" => $machine['machine_name'],
                "type" => $postData['type'],
                "check_batch_no" => $checkBatchNo,
                "ao_id" => $machine['ao_id'],
                "create_date" => strtotime(date("Y-m-d")),
                "creator" => $this->tokenArr['manager_id'],
            ];
            // 旧接口保持兼容，但同样只登记观察值，不生成未实际发生的库存变化流水。
            $insertAll = [];
            foreach ($checkList as $ck => $cv) {
                validate(VMachineCheck::class)->scene("checkList" . $postData['type'])->check($cv);
                $insertCs = array_merge($insert, [
                    'type' => intval($postData['type']),
                    'check_stock' => intval($cv['check_stock']),
                ]);
                if ($postData['type'] == 1 && $cv['mc_id'] && $cv['mc_id'] > 0) {
                    $mc = $this->getMachineChannelFind(
                        ['mc_id' => $cv['mc_id'], 'm_id' => $machine['m_id']],
                        'mc_id,channel_code,mg_id,g_id,g_name,pic,sku,bar_code,gc_id,gc_id,gc_name,stock system_stock'
                    );
                    if (!$mc) {
                        $this->rollbackTrans();
                        return $this->rFail($this->lang("MachineCheck.mc_no_data"));
                    }
                    $mc = $mc->toArray();
                    unset($mc['bar_code']);

                    $insertCs['status'] = $this->resolveCheckStockStatus($cv['check_stock'], $mc['system_stock']);
                    $insertCs = array_merge($insertCs,$mc);
                }
                if ($postData['type'] == 2 && $cv['mg_id'] && $cv['mg_id'] > 0) {
                    $mg = $this->getMachineGoodsFind(
                        ['mg_id' => $cv['mg_id'], 'm_id' => $machine['m_id']],
                        'mg_id,g_id,g_name,pic,sku,bar_code,gc_id,gc_name, standby_stock system_stock'
                    );
                    if (!$mg) {
                        $this->rollbackTrans();
                        return $this->r(100, $this->lang("MachineCheck.mg_no_data"));
                    }
                    $mg = $mg->toArray();
                    $insertCs['status'] = $this->resolveCheckStockStatus($cv['check_stock'], $mg['system_stock']);
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

    protected function resolveCheckStockStatus($checkStock, $systemStock)
    {
        if (intval($checkStock) === intval($systemStock)) return 1;
        return intval($checkStock) > intval($systemStock) ? 2 : 3;
    }

}
