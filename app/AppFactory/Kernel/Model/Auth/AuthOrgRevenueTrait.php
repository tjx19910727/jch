<?php
/**
 * 组织分账日志操作 Trait
 */
namespace app\AppFactory\Kernel\Traits\Auth;

use app\AppFactory\Kernel\Model\Auth\AuthOrgRevenueLogModel;

trait AuthOrgRevenueTrait
{
    public function addAuthOrgRevenueLog($insert)
    {
        if (!isset($insert['ao_id'])) $insert['ao_id'] = 0;
        $insert['create_time'] = time();
        return AuthOrgRevenueLogModel::insertOneGetId($insert);
    }

    public function getAuthOrgRevenueLogFind($where, $field = '*')
    {
        return AuthOrgRevenueLogModel::getFind($where, $field);
    }

    public function getAuthOrgRevenueLogList($where, $pageNum = 0, $field = '*', $order = '')
    {
        return AuthOrgRevenueLogModel::getList($where, $pageNum, $field, $order);
    }
}
