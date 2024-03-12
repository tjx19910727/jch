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

    /**
     * 终端补货上报
     * @return mixed
     */
    public function terminalReplenishment()
    {
        if ($this->data['operator'] != $this->machine['manager_id'] && $this->data['operator'] != $this->machine['tally_clerk'])
            return $this->rFail($this->lang("VChannelReplenishment.non-administrators"));
        $this->data['repList'] = json2arr($this->data['repList']);
        $flag = [];
        $this->startTrans();
        foreach ($this->data['repList'] as $key => $value) {
            try {
                validate(VChannelReplenishment::class)->scene("replenishment")->check($value);
            } catch (\Exception $e) {
                $this->rollbackTrans();
                return $this->rValidate($this->lang($e->getMessage()));
            }
            $mc = $this->getMachineChannelFind(['mc_id' => $value['mc_id']]);
            if (!$mc) {
                $this->rollbackTrans();
                return $this->rFail($this->lang("VChannelReplenishment.channel_no_data"));
            }
            if (bcadd($mc['stock'],$value['quantity']) > $mc['capacity']) {
                $this->rollbackTrans();
                return $this->rFail($this->lang("VChannelReplenishment.exceed_capacity_limit"));
            }
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
            "m_id" => $mc['m_id'],
            "machine_id" => $mc['machine_id'],
            "mc_id" => $mc['mc_id'],
            "channel_code" => $mc['channel_code'],
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