<?php

namespace app\AppFactory\Management\Config;

use app\AppFactory\Kernel\Traits\SaleOrders\OrderTypeTrait;
use app\AppFactory\Management\ManagementClient;

class OrderTypeClient extends ManagementClient
{
    use OrderTypeTrait;

    public function getList($where = [], $pageNum = 0, $field = "*", $order = "sort asc, order_type asc")
    {
        return $this->rQ($this->getOrderTypeList($where, $pageNum, $field, $order));
    }

    public function getFind($where = [], $field = "*", $order = "ot_id desc")
    {
        return $this->rQ($this->getOrderTypeFind($where, $field, $order));
    }

    public function addData($postData)
    {
        $postData = $this->normalizeData($postData);
        $check = $this->checkData($postData);
        if ($check !== true) return $check;
        if (!isset($postData['status'])) $postData['status'] = 1;
        if (!isset($postData['sort'])) $postData['sort'] = 0;
        return $this->rA($this->addOrderType($postData));
    }

    public function updateData($postData)
    {
        if (empty($postData['ot_id'])) return $this->rFail("订单类型配置ID不能为空");
        $postData = $this->normalizeData($postData);
        $check = $this->checkData($postData, true);
        if ($check !== true) return $check;
        $otId = intval($postData['ot_id']);
        unset($postData['ot_id']);
        return $this->rU($this->updateOrderType($postData, ['ot_id' => $otId]));
    }

    public function delData($otId)
    {
        return $this->rD($this->delOrderType(['ot_id' => intval($otId)]));
    }

    protected function normalizeData($data)
    {
        if (isset($data['order_type'])) $data['order_type'] = intval($data['order_type']);
        if (isset($data['status'])) $data['status'] = intval($data['status']);
        if (isset($data['sort'])) $data['sort'] = intval($data['sort']);
        if (isset($data['order_type_name'])) $data['order_type_name'] = trim($data['order_type_name']);
        if (isset($data['remark'])) $data['remark'] = trim($data['remark']);
        return $data;
    }

    protected function checkData($data, $isUpdate = false)
    {
        if (!$isUpdate || isset($data['order_type'])) {
            if (!isset($data['order_type']) || $data['order_type'] === '') return $this->rFail("订单类型不能为空");
            if (intval($data['order_type']) < 0) return $this->rFail("订单类型不合法");
            $exists = $this->getOrderTypeFind(['order_type' => intval($data['order_type'])], 'ot_id');
            if ($exists && (!$isUpdate || intval($exists['ot_id']) !== intval($data['ot_id'] ?? 0))) {
                return $this->rFail("该订单类型已存在");
            }
        }
        if (!$isUpdate || isset($data['order_type_name'])) {
            if (!isset($data['order_type_name']) || $data['order_type_name'] === '') return $this->rFail("订单类型名称不能为空");
        }
        if (isset($data['status']) && !in_array(intval($data['status']), [1, 2], true)) {
            return $this->rFail("状态不合法");
        }
        return true;
    }
}
