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

    public function getAuthOrgMCList($where, $column)
    {
        return AuthOrgMachineChannelModel::getList($where, $column);
    }

    public function getAuthOrgMCColumn($where, $column)
    {
        return AuthOrgMachineChannelModel::getColumn($where, $column);
    }


    public function addAuthOrgMC($insert)
    {
        // 尝试基于 ao_id + m_id + machine_id 找到已存在记录，若存在则更新 channel_code 并返回 id（幂等化）
        $where = [
            'ao_id' => $insert['ao_id'] ?? 0,
            'm_id' => $insert['m_id'] ?? 0,
            'machine_id' => $insert['machine_id'] ?? '',
        ];
        $exists = AuthOrgMachineChannelModel::getFind($where, 'id');
        if ($exists && isset($exists['id'])) {
            $update = [];
            if (isset($insert['channel_code'])) $update['channel_code'] = $insert['channel_code'];
            $update['update_time'] = $insert['update_time'] ?? time();
            AuthOrgMachineChannelModel::update($update, ['id' => $exists['id']]);
            return $this->updateAuthOrgMC($insert,['id'=>$exists['id']]);
        }

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
