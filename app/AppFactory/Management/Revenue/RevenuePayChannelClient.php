<?php

namespace app\AppFactory\Management\Revenue;

use app\AppFactory\Kernel\Traits\Revenue\RevenuePayChannelTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Management\ManagementClient;

class RevenuePayChannelClient extends ManagementClient
{
    use RevenuePayChannelTrait;
    use RevenuePayTypeDescTrait;
    use SaleOrdersTrait;

    public function addData($postData)
    {
        unset($postData['payee_type'], $postData['settlement_type'], $postData['settlement_days']);
        $this->normalizePayChannelData($postData);
        $check = $this->checkData($postData);
        if ($check !== true) return $check;
        if (!isset($postData['status'])) $postData['status'] = 1;
        return $this->rA($this->addRevenuePayChannel($postData));
    }

    public function updateData($postData)
    {
        unset($postData['payee_type'], $postData['settlement_type'], $postData['settlement_days']);
        if (empty($postData['rpc_id'])) return $this->rFail("分账渠道配置ID不能为空");
        $this->normalizePayChannelData($postData);
        $check = $this->checkData($postData, true);
        if ($check !== true) return $check;
        $rpcId = intval($postData['rpc_id']);
        unset($postData['rpc_id']);
        return $this->rU($this->updateRevenuePayChannel($postData, ['rpc_id' => $rpcId]));
    }

    public function getList($where= [], $pageNum = 0, $field = "*", $order = "rpc_id desc",$rQ = 1)
    {
        return $this->rQ($this->appendRevenuePayTypeDesc(
            $this->getRevenuePayChannelList($where, $pageNum, $field, $order)
        ));
    }

    public function getFind($where= [], $field = "*", $order = "rpc_id desc",$rQ = 1)
    {
        return $this->rQ($this->appendRevenuePayTypeDesc(
            $this->getRevenuePayChannelFind($where, $field, $order)
        ));
    }

    public function delData($rpcId)
    {
        return $this->rD($this->delRevenuePayChannel(['rpc_id' => $rpcId]));
    }

    protected function checkData(&$data, $isUpdate = false)
    {
        if (!$isUpdate || isset($data['pay_channel'])) {
            if (!isset($data['pay_channel']) || intval($data['pay_channel']) <= 0) {
                return $this->rFail("分账支付渠道不能为空，请传入pay_channel或可映射的pay_type");
            }
            $where = ['pay_channel' => intval($data['pay_channel'])];
            $exists = $this->getRevenuePayChannelFind($where, 'rpc_id');
            if ($exists && (!$isUpdate || intval($exists['rpc_id']) !== intval($data['rpc_id'] ?? 0))) {
                return $this->rFail("该支付渠道已配置分账渠道");
            }
        }
        if (isset($data['pay_type']) && $data['pay_type'] !== '') {
            $payType = intval($data['pay_type']);
            if ($payType < 0) return $this->rFail("支付类型不合法");
            $where = ['pay_type' => $payType];
            $exists = $this->getRevenuePayChannelFind($where, 'rpc_id');
            if ($exists && (!$isUpdate || intval($exists['rpc_id']) !== intval($data['rpc_id'] ?? 0))) {
                return $this->rFail("该支付类型已配置分账渠道");
            }
        }
        if (isset($data['status']) && !in_array(intval($data['status']), [1, 2], true)) {
            return $this->rFail("状态不合法");
        }
        return true;
    }

    protected function normalizePayChannelData(&$data)
    {
        if (!is_array($data)) return;
        if ((!isset($data['pay_channel']) || intval($data['pay_channel']) <= 0)
            && isset($data['pay_type'])
            && $data['pay_type'] !== '') {
            $result = $this->buildOrderPayChannel([
                'pay_type' => intval($data['pay_type']),
                'has_wc_order_no' => 0,
            ]);
            $data['pay_channel'] = intval($result['pay_channel'] ?? 0);
            if (empty($data['channel_name'])) {
                $data['channel_name'] = $result['pay_channel_name'] ?? '';
            }
        }
        if (isset($data['pay_channel']) && intval($data['pay_channel']) > 0 && empty($data['channel_name'])) {
            $data['channel_name'] = $this->getPayChannelName(intval($data['pay_channel']));
        }
    }
}
