<?php

/**
 * Trait: 组织提现申请相关封装
 */

namespace app\AppFactory\Kernel\Traits\Auth;


use app\AppFactory\Kernel\Model\Auth\AuthWithdrawRequestModel;

trait AuthWithdrawRequestTrait
{
    /**
     * 创建提现申请，返回插入 id
     */
    public function addAuthWithdrawRequest($insert)
    {
        $id = AuthWithdrawRequestModel::insertOneGetId($insert);
        return $id;
    }

    public function getAuthWithdrawRequestFind($where, $field = '*')
    {
        return AuthWithdrawRequestModel::getFind($where, $field);
    }

    public function getAuthWithdrawRequestList($where, $pageNum = '', $field = '*', $order = '', $eachFn = "", $group = "", $limit = 0, $with = [])
    {
        return AuthWithdrawRequestModel::getListAndWith($where, $pageNum, $field, $order, $eachFn, $group, $limit, ['managerData','auditData']);
    }

    public function updateAuthWithdrawRequest($update)
    {
        if (!isset($update['wr_id'])) return false;
        $where = ['wr_id' => $update['wr_id']];
        $field = [];
        return AuthWithdrawRequestModel::update($update, $where, $field);
    }
}
