<?php

namespace app\AppFactory\Management\Revenue;

use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Revenue\RevenueAccountTrait;
use app\AppFactory\Management\ManagementClient;

class RevenueAccountClient extends ManagementClient
{
    use RevenueAccountTrait;
    use AuthManagerTrait;

    public function add($postData)
    {
        $check = $this->checkAccountData($postData);
        if ($check !== true) return $check;
        if (!isset($postData['status'])) $postData['status'] = 1;
        return $this->rA($this->addRevenueAccount($postData));
    }

    public function update($postData)
    {
        if (empty($postData['ra_id'])) return $this->rFail("分账账户ID不能为空");
        $check = $this->checkAccountData($postData, true);
        if ($check !== true) return $check;
        return $this->rU($this->updateRevenueAccount($postData, [], ['ra_id']));
    }

    public function getList($where, $pageNum = 0, $field = "*", $order = "ra_id desc")
    {
        return $this->rQ($this->getRevenueAccountList($where, $pageNum, $field, $order));
    }

    public function getFind($where, $field = "*", $order = "ra_id desc")
    {
        return $this->rQ($this->getRevenueAccountFind($where, $field, $order));
    }

    public function del($raId)
    {
        return $this->rD($this->delRevenueAccount(['ra_id' => $raId]));
    }

    protected function checkAccountData($data, $isUpdate = false)
    {
        if (!$isUpdate || isset($data['ao_id'])) {
            if (empty($data['ao_id'])) return $this->rFail("所属组织不能为空");
        }
        if (!$isUpdate || isset($data['manager_id'])) {
            if (empty($data['manager_id'])) return $this->rFail("账户管理人不能为空");
            $manager = $this->getAuthManagerFind(['manager_id' => $data['manager_id']], 'manager_id,ao_id,status');
            if (!$manager) return $this->rFail("账户管理人不存在");
            if (isset($data['ao_id']) && intval($manager['ao_id']) !== intval($data['ao_id'])) {
                return $this->rFail("账户管理人所属组织与分账账户组织不一致");
            }
            if (isset($manager['status']) && intval($manager['status']) !== 1) {
                return $this->rFail("账户管理人未启用");
            }
        }
        if (!$isUpdate || isset($data['account_type'])) {
            if (empty($data['account_type'])) return $this->rFail("账户类型不能为空");
        }
        return true;
    }
}
