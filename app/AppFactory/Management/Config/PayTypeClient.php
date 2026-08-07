<?php

namespace app\AppFactory\Management\Config;

use app\AppFactory\Kernel\Traits\Payment\PayTypeTrait;
use app\AppFactory\Management\ManagementClient;

class PayTypeClient extends ManagementClient
{
    use PayTypeTrait;

    public function getList($where = [], $pageNum = 0, $field = "*", $order = "sort asc, pay_type asc", $rQ = 1)
    {
        $data = $this->getPayTypeList($where, $pageNum, $field, $order);
        return $rQ ? $this->rQ($data) : $data;
    }

    public function getFind($where = [], $field = "*", $order = "pt_id desc", $rQ = 1)
    {
        $data = $this->getPayTypeFind($where, $field, $order);
        return $rQ ? $this->rQ($data) : $data;
    }

    public function getTree($where = [])
    {
        $sceneMap = [
            1 => '线上支付',
            2 => '线下支付',
        ];
        $tree = [];
        foreach ($sceneMap as $scene => $label) {
            $tree[$scene] = [
                'value' => $scene,
                'label' => $label,
                'pay_scene' => $scene,
                'pay_scene_name' => $label,
                'children' => [],
            ];
        }

        $list = $this->getPayTypeList($where, 0, 'pt_id,pay_type,pay_type_name,pay_scene,status,sort,remark', 'pay_scene asc, sort asc, pay_type asc');
        foreach ($list as $item) {
            $scene = intval($item['pay_scene'] ?? 1);
            if (!isset($tree[$scene])) {
                $tree[$scene] = [
                    'value' => $scene,
                    'label' => '支付场景#' . $scene,
                    'pay_scene' => $scene,
                    'pay_scene_name' => '支付场景#' . $scene,
                    'children' => [],
                ];
            }
            $payType = intval($item['pay_type'] ?? 0);
            $name = trim((string)($item['pay_type_name'] ?? ''));
            $tree[$scene]['children'][] = [
                'value' => $payType,
                'label' => $name,
                'pt_id' => intval($item['pt_id'] ?? 0),
                'pay_type' => $payType,
                'pay_type_name' => $name,
                'pay_scene' => $scene,
                'status' => intval($item['status'] ?? 0),
                'sort' => intval($item['sort'] ?? 0),
                'remark' => trim((string)($item['remark'] ?? '')),
            ];
        }

        return $this->rQ(array_values($tree));
    }

    public function addData($postData)
    {
        $postData = $this->normalizeData($postData);
        $check = $this->checkData($postData);
        if ($check !== true) return $check;
        if (!isset($postData['status'])) $postData['status'] = 1;
        if (!isset($postData['pay_scene'])) $postData['pay_scene'] = 1;
        if (!isset($postData['sort'])) $postData['sort'] = 0;
        return $this->rA($this->addPayType($postData));
    }

    public function updateData($postData)
    {
        if (empty($postData['pt_id'])) return $this->rFail("支付类型配置ID不能为空");
        $postData = $this->normalizeData($postData);
        $check = $this->checkData($postData, true);
        if ($check !== true) return $check;
        $ptId = intval($postData['pt_id']);
        unset($postData['pt_id']);
        return $this->rU($this->updatePayType($postData, ['pt_id' => $ptId]));
    }

    public function delData($ptId)
    {
        return $this->rD($this->delPayType(['pt_id' => intval($ptId)]));
    }

    protected function normalizeData($data)
    {
        if (isset($data['pay_type'])) $data['pay_type'] = intval($data['pay_type']);
        if (isset($data['pay_scene'])) $data['pay_scene'] = intval($data['pay_scene']);
        if (isset($data['status'])) $data['status'] = intval($data['status']);
        if (isset($data['sort'])) $data['sort'] = intval($data['sort']);
        if (isset($data['pay_type_name'])) $data['pay_type_name'] = trim($data['pay_type_name']);
        if (isset($data['remark'])) $data['remark'] = trim($data['remark']);
        return $data;
    }

    protected function checkData($data, $isUpdate = false)
    {
        if (!$isUpdate || isset($data['pay_type'])) {
            if (!isset($data['pay_type']) || $data['pay_type'] === '') return $this->rFail("支付类型不能为空");
            if (intval($data['pay_type']) < 0) return $this->rFail("支付类型不合法");
            $exists = $this->getPayTypeFind(['pay_type' => intval($data['pay_type'])], 'pt_id');
            if ($exists && (!$isUpdate || intval($exists['pt_id']) !== intval($data['pt_id'] ?? 0))) {
                return $this->rFail("该支付类型已存在");
            }
        }
        if (!$isUpdate || isset($data['pay_type_name'])) {
            if (!isset($data['pay_type_name']) || $data['pay_type_name'] === '') return $this->rFail("支付类型名称不能为空");
        }
        if (isset($data['pay_scene']) && !in_array(intval($data['pay_scene']), [1, 2], true)) {
            return $this->rFail("线上线下支付标记不合法");
        }
        if (isset($data['status']) && !in_array(intval($data['status']), [1, 2], true)) {
            return $this->rFail("状态不合法");
        }
        return true;
    }
}
