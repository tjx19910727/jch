<?php

namespace app\AppFactory\Management\Revenue;

use app\AppFactory\Kernel\Traits\Revenue\RevenuePayChannelTrait;
use app\AppFactory\Management\ManagementClient;

class RevenuePayChannelClient extends ManagementClient
{
    use RevenuePayChannelTrait;

    public function add($postData)
    {
        $check = $this->checkData($postData);
        if ($check !== true) return $check;
        if (!isset($postData['status'])) $postData['status'] = 1;
        if (!isset($postData['settlement_type'])) $postData['settlement_type'] = 1;
        if (!isset($postData['settlement_days'])) $postData['settlement_days'] = 0;
        return $this->rA($this->addRevenuePayChannel($postData));
    }

    public function update($postData)
    {
        if (empty($postData['rpc_id'])) return $this->rFail("分账渠道配置ID不能为空");
        $check = $this->checkData($postData, true);
        if ($check !== true) return $check;
        return $this->rU($this->updateRevenuePayChannel($postData, [], ['rpc_id']));
    }

    public function getList($where, $pageNum = 0, $field = "*", $order = "rpc_id desc")
    {
        return $this->rQ($this->getRevenuePayChannelList($where, $pageNum, $field, $order));
    }

    public function getFind($where, $field = "*", $order = "rpc_id desc")
    {
        return $this->rQ($this->getRevenuePayChannelFind($where, $field, $order));
    }

    public function del($rpcId)
    {
        return $this->rD($this->delRevenuePayChannel(['rpc_id' => $rpcId]));
    }

    protected function checkData(&$data, $isUpdate = false)
    {
        if (!$isUpdate || isset($data['pay_type'])) {
            if (!isset($data['pay_type']) || intval($data['pay_type']) <= 0) {
                return $this->rFail("订单支付类型不能为空");
            }
            $where = ['pay_type' => intval($data['pay_type'])];
            $exists = $this->getRevenuePayChannelFind($where, 'rpc_id');
            if ($exists && (!$isUpdate || intval($exists['rpc_id']) !== intval($data['rpc_id'] ?? 0))) {
                return $this->rFail("该支付类型已配置分账渠道");
            }
        }
        if (isset($data['payee_type']) && $data['payee_type'] !== '' && intval($data['payee_type']) <= 0) {
            return $this->rFail("收款策略类型不合法");
        }
        if (isset($data['status']) && !in_array(intval($data['status']), [1, 2], true)) {
            return $this->rFail("状态不合法");
        }
        $settlementData = $data;
        if ($isUpdate && (!isset($data['settlement_type']) || !isset($data['settlement_days']))) {
            $current = $this->getRevenuePayChannelFind(['rpc_id' => intval($data['rpc_id'] ?? 0)]);
            if ($current) {
                $settlementData = array_merge(is_array($current) ? $current : $current->toArray(), $data);
            }
        }
        $settlementType = intval($settlementData['settlement_type'] ?? 1);
        if (isset($data['settlement_type']) && !in_array($settlementType, [1, 2], true)) {
            return $this->rFail("分账时间类型不合法");
        }
        if (isset($data['settlement_days']) && intval($settlementData['settlement_days']) < 0) {
            return $this->rFail("T+N 天数不能小于0");
        }
        if ($settlementType === 2 && intval($settlementData['settlement_days'] ?? 0) < 1) {
            return $this->rFail("T+N 分账天数必须大于0");
        }
        if ($settlementType === 1 && (isset($data['settlement_type']) || isset($data['settlement_days']))) {
            $data['settlement_days'] = 0;
        }
        return true;
    }
}
