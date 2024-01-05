<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/11
 * Time: 13:59
 */

namespace app\AppFactory\Kernel\Traits\Auth;


use app\AppFactory\Kernel\Model\Withdrawal\AuthManagerBankModel;

trait AuthManagerBankTrait
{
    public function getAuthManagerBankList($where,$pageNum = 0,$field = "*", $order = "amb_id desc")
    {
        return AuthManagerBankModel::getList($where,$pageNum,$field,$order);
    }

    public function getAuthManagerBankFind($where,$field = "*", $order = "amb_id desc")
    {
        return AuthManagerBankModel::getFind($where,$field,$order);
    }

    public function addAuthManagerBank($insert)
    {
        $insert['manager_id'] = $this->manager['manager_id'] ?? 0;
        $amb = AuthManagerBankModel::create($insert);
        return $amb->amb_id;
    }

    public function updateAuthManagerBank($update,$where = [], $field = [])
    {
        return AuthManagerBankModel::update($update,$where,$field);
    }

}