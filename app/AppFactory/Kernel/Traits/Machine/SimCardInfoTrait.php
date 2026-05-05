<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/21
 * Time: 11:30
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\SimCardInfoModel;
use app\AppFactory\Kernel\Model\Machine\SimCardMachineModel;

trait SimCardInfoTrait
{
    public function getSimCardInfoListJoinMachine($where, $pageNum = null, $field = "a.*", $order = "a.id desc", $eachFun = "", $group = '', $limit = '')
    {
        $join = [[
            'join' => 'machine m',
            'on' => 'm.machine_id = a.machine_id',
            'type' => 'inner',
        ]];
        return SimCardInfoModel::getListAndWith($where, $pageNum, $field, $order, $eachFun, $group, $limit, [], $join);
    }

    public function getSimCardMachineListJoinMachine($where, $pageNum = null, $field = "a.*", $order = "a.id desc", $eachFun = "", $group = '', $limit = '')
    {
        $join = [[
            'join' => 'machine m',
            'on' => 'm.machine_id = a.machine_id',
            'type' => 'inner',
        ]];
        return SimCardMachineModel::getListAndWith($where, $pageNum, $field, $order, $eachFun, $group, $limit, [], $join);
    }


    public function getSimCardInfoColumn($where, $column, $key = "")
    {
        return SimCardInfoModel::getColumn($where, $column, $key);
    }

    public function getSimCardInfoValue($where, $value, $order = "")
    {
        return SimCardInfoModel::getFieldValue($where, $value, $order);
    }

    public function getSimCardInfoCount($where)
    {
        return SimCardInfoModel::getCount($where);
    }

    public function getSimCardInfoFind($where, $field = "*", $order = "")
    {
        return SimCardInfoModel::getFind($where, $field, $order);
    }

    public function getSimCardInfoList($where, $pageNum = null, $field = "*", $order = "", $eachFun = "", $group = '', $limit = '')
    {
        return SimCardInfoModel::getList($where, $pageNum, $field, $order, $eachFun, $group, $limit);
    }

    public function addSimCardInfo($insert)
    {
        $data = SimCardInfoModel::create($insert);
        return $data->id;
    }

    public function updateSimCardInfo($update, $where = [], $field = [])
    {
        return SimCardInfoModel::update($update, $where, $field);
    }

    public function delSimCardInfo($where)
    {
        return SimCardInfoModel::whereDel($where);
    }

    public function getSimCardMachineValue($where, $value, $order = "")
    {
        return SimCardMachineModel::getFieldValue($where, $value, $order);
    }

    public function getSimCardMachineFind($where, $field = "*", $order = "")
    {
        return SimCardMachineModel::getFind($where, $field, $order);
    }

    public function getSimCardMachineList($where, $pageNum = null, $field = "*", $order = "", $eachFun = "", $group = '', $limit = '')
    {
        return SimCardMachineModel::getList($where, $pageNum, $field, $order, $eachFun, $group, $limit);
    }

    public function addSimCardMachine($insert)
    {
        $data = SimCardMachineModel::create($insert);
        return $data->id;
    }

    public function updateSimCardMachine($update, $where = [], $field = [])
    {
        return SimCardMachineModel::update($update, $where, $field);
    }
}
