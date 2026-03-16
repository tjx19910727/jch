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


    public function getAuthOrgMCFind($where, $column = [])
    {
        return AuthOrgMachineChannelModel::getFind($where, $column);
    }

    public function getAuthOrgMCList($where, $column = [])
    {
        return AuthOrgMachineChannelModel::getList($where, $column);
    }

    public function getAuthOrgMCColumn($where, $column = [])
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
