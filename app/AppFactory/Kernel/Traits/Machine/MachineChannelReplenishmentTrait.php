<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/1
 * Time: 18:02
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineChannelReplenishmentModel;
use app\AppFactory\Kernel\Support\Validate\Machine\VChannelReplenishment;

trait MachineChannelReplenishmentTrait
{
    public function getMachineChannelReplenishmentFind($where, $field = "*", $order = "")
    {
        return MachineChannelReplenishmentModel::getFind($where, $field, $order);
    }

    public function getMachineChannelReplenishmentList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "")
    {
        return MachineChannelReplenishmentModel::getList($where, $pageNum, $field, $order, $eachFun);
    }

    public function addMachineChannelReplenishment($insert)
    {
        $data = MachineChannelReplenishmentModel::create($insert);
        return $data->id;
    }

    public function delMachineChannelReplenishment($where)
    {
        $result = MachineChannelReplenishmentModel::whereDel($where);
        return $result;
    }

    /**
     * 终端补货上报
     * @return mixed
     */
    public function terminalReplenishment()
    {
        $amm = $this->getAuthManagerMachineColumn(['m_id' => $this->machine['m_id']],'manager_id');
        if (!in_array($this->data['operator'],$amm))
            return $this->rFail($this->lang("VChannelReplenishment.non-administrators"));
        $this->data['repList'] = json2arr($this->data['repList']);
        $flag = [];
        $this->startTrans();
        $insertGChange = [
            "m_id" => $this->machine['m_id'],
            "machine_id" => $this->machine['machine_id'],
            "machine_name" => $this->machine['machine_name'],
            "ao_id" => $this->machine['ao_id'],
            "creator" => $this->data['operator'],
        ];
        foreach ($this->data['repList'] as $key => $value) {
            try {
                validate(VChannelReplenishment::class)->scene("replenishment")->check($value);
            } catch (\Exception $e) {
                $this->rollbackTrans();
                return $this->rValidate($this->lang($e->getMessage()));
            }
            $insertGc = $insertGChange;
            $mc = $this->getMachineChannelFind(['mc_id' => $value['mc_id']]);
            if (!$mc) {
                $this->rollbackTrans();
                return $this->rFail($this->lang("VChannelReplenishment.channel_no_data"));
            }
            if (bcadd($mc['stock'],$value['quantity']) > $mc['capacity']) {
                $this->rollbackTrans();
                return $this->rFail($this->lang("VChannelReplenishment.exceed_capacity_limit"));
            }
            $insertGc = array_merge($insertGc,[
                "mc_id" => $mc['mc_id'],
                "channel_code" => $mc['channel_code'],
                "mg_id" => $mc['mg_id'],
                "g_id" => $mc['g_id'],
                "g_name" => $mc['g_name'],
                "gc_id" => $mc['gc_id'],
                "gc_name" => $mc['gc_name'],
                "pic" => $mc['pic'],
                "sku" => $mc['sku'],
                "bar_code" => $mc['bar_code'],
                "change_value" => abs($value['quantity']),
            ]);
            if ($mc['mg_id'] > 0) {
                $mg = $this->getMachineGoodsFind(['mg_id' => $mc['mg_id']],'mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price,standby_stock');
                if (!$mg) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VChannelReplenishment.mg_no_data") . $mc['channel_code']);
                }
                if (is_object($mg)) $mg = $mg->toArray();
                if ($mg['standby_stock'] < $value['quantity']) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VChannelReplenishment.exceed_standby_stock_limit"));
                }
                // 补货时使用了备用库存
                $insertGc['desc'] = "终端补货-设备商品下货备用库存";
                $insertGc['position'] = 2;
                $insertGc['type'] = ($value['quantity'] > 0 ? 3 : 2);
                $this->addGoodsChange($insertGc);

                $flag[] = $this->setMachineGoodsDec(['mg_id' => $mg['mg_id']],'standby_stock',$value['quantity']);
            }

            $insertGc['desc'] = "终端补货-货架上货库存";
            $insertGc['position'] = 1;
            $insertGc['type'] = ($value['quantity'] > 0 ? 2 : 3);
            // 记录商品变化事件（货架上货）
            $this->addGoodsChange($insertGc);


            $repData = $this->handleRepData($mc,$value['quantity']);
            $flag[] = $this->addMachineChannelReplenishment($repData);
            $flag[] = $this->updateMachineChannel(['mc_id' => $mc['mc_id'],'stock' => bcadd($mc['stock'],$value['quantity'])]);
        }
        $result = $this->checkFlag($flag);
        return $this->checkTrans($result);
    }

    /**
     * 整理补货数据
     * @param $mc
     * @param $quantity
     * @param int $creator
     * @return array
     */
    protected function handleRepData($mc,$quantity)
    {
        $repData = [
            "m_id" => $this->machine['m_id'],
            "machine_id" => $this->machine['machine_id'],
            "machine_name" => $this->machine['machine_name'],
            "mc_id" => $mc['mc_id'],
            "channel_code" => $mc['channel_code'],
            "mg_id" => $mc['mg_id'],
            "g_id" => $mc['g_id'],
            "g_name" => $mc['g_name'],
            "gc_id" => $mc['gc_id'],
            "gc_name" => $mc['gc_name'],
            "pic" => $mc['pic'],
            "sku" => $mc['sku'],
            "bar_code" => $mc['bar_code'],
            "batch_number" => $mc['batch_number'],
            "before" => $mc['stock'],
            "quantity" => $quantity,
            "after" => bcadd($mc['stock'],$quantity),
            "creator" => $this->data['operator'] ?? 0
        ];
        return $repData;
    }



}