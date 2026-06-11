<?php

namespace app\AppFactory\Management\Revenue;

use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Revenue\RevenueAccountTrait;
use app\AppFactory\Management\ManagementClient;

class RevenueAccountClient extends ManagementClient
{
    use RevenueAccountTrait;
    use AuthManagerTrait;

    public function addData($postData = [])
    {
        $check = $this->checkAccountData($postData);
        if ($check !== true) return $check;
        if (!isset($postData['status'])) $postData['status'] = 1;
        return $this->rA($this->addRevenueAccount($postData));
    }

    public function updateData($postData = [])
    {
        if (empty($postData['ra_id'])) return $this->rFail("分账账户ID不能为空");
        $check = $this->checkAccountData($postData, true);
        if ($check !== true) return $check;
        $raId = intval($postData['ra_id']);
        unset($postData['ra_id']);
        return $this->rU($this->updateRevenueAccount($postData, ['ra_id' => $raId]));
    }

    public function getList($where = [], $pageNum = 0, $field = "*", $order = "ra_id desc", $rQ = 1)
    {
        $data = $this->getRevenueAccountList($where, $pageNum, $field, $order);
        if ($rQ) return $this->rQ($data);
        return $data;
    }

    public function getFind($where = [], $field = "*", $order = "ra_id desc", $rQ = 1)
    {
        $data = $this->getRevenueAccountFind($where, $field, $order);
        if ($rQ) return $this->rQ($data);
        return $data;
    }

    public function delData($where, $rD = 1)
    {
        // allow passing a single id as before
        if (is_int($where) || (is_string($where) && ctype_digit($where))) {
            $where = ['ra_id' => intval($where)];
        }
        $result = $this->delRevenueAccount($where);
        if ($rD) return $this->rD($result);
        return $result;
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
