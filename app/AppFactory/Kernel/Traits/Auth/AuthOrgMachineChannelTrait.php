<?php

/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 2024/3/9
 * Time: 14:45
 */

namespace app\AppFactory\Kernel\Traits\Auth;


use app\AppFactory\Kernel\Model\Auth\AuthOrgMachineChannelModel;

trait AuthOrgMachineChannelTrait
{


    public function getAuthOrgMCFind($where, $field = "*", $order = "")
    {
        return AuthOrgMachineChannelModel::getFind($where, $field, $order);
    }

    public function getAuthOrgMCList($where, $pageNum = null, $field = '*', $order = '')
    {
        return AuthOrgMachineChannelModel::getList($where, $pageNum = null, $field = "*", $order = "", $eachFn = "", $group = "", $limit = 0);
    }

    public function getAuthOrgMCColumn($where, $column)
    {
        return AuthOrgMachineChannelModel::getColumn($where, $column);
    }


    public function addAuthOrgMC($insert)
    {
        $data = AuthOrgMachineChannelModel::create($insert);
        return $data->id;
    }

    public function updateAuthOrgMC($update, $where = [], $field = [])
    {
        return AuthOrgMachineChannelModel::update($update, $where, $field);
    }

    public function delAuthOrgMC($where)
    {
        return AuthOrgMachineChannelModel::whereDel($where);
    }
}
