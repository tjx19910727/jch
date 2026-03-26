<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/03/20
 * Time: 10:05
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineAuxiliaryModel;

trait MachineAuxiliaryTrait
{
    /**
     * 获取副柜指定列数据
     * @param $where
     * @param $column
     * @return array
     */
    public function getMachineAuxiliaryColumn($where,$column)
    {
        return MachineAuxiliaryModel::getColumn($where,$column);
    }

    /**
     * 获取副柜字段数值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getMachineAuxiliaryValue($where,$value)
    {
        return MachineAuxiliaryModel::getFieldValue($where,$value);
    }

    public function getMachineAuxiliaryCount($where)
    {
        return MachineAuxiliaryModel::getCount($where);
    }

    /**
     * 获取一条副柜信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return MachineAuxiliaryModel|array|mixed|null|\think\Model
     */
    public function getMachineAuxiliaryFind($where,$field = "*",$order = "")
    {
        return MachineAuxiliaryModel::getFind($where,$field,$order);
    }

    /**
     * 获取副柜列表
     * @param $where
     * @param int|array $pageNum
     * @param string $field
     * @param string $order
     * @param string $eachFun
     * @param string $group
     * @param string $limit
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|\think\Collection|\think\Paginator
     */
    public function getMachineAuxiliaryList($where,$pageNum = null,$field = "*", $order = "",$eachFun = "",$group = '', $limit = '')
    {
        return MachineAuxiliaryModel::getList($where,$pageNum,$field,$order,$eachFun,$group,$limit);
    }

    /**
     * 添加副柜信息
     * @param $insert
     * @return mixed
     */
    public function addMachineAuxiliary($insert)
    {
        $data = MachineAuxiliaryModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改副柜信息
     * @param $update
     * @param array $where
     * @param array $field
     * @return MachineAuxiliaryModel
     */
    public function updateMachineAuxiliary($update,$where = [],$field = [])
    {
        return MachineAuxiliaryModel::update($update,$where,$field);
    }

    /**
     * 删除副柜信息
     * @param $where
     * @return bool
     */
    public function delMachineAuxiliary($where)
    {
        return MachineAuxiliaryModel::whereDel($where);
    }

    /**
     * 批量添加副柜信息
     * @param $insertAll
     * @return \think\Collection
     */
    public function addMachineAuxiliaryMore($insertAll)
    {
        return MachineAuxiliaryModel::saveAll($insertAll);
    }

    public function getMachineAuxiliaryMachineColumn($where,$field = "*",$order = "m_id desc",$column = 'machine_id')
    {
        $data = MachineAuxiliaryModel::where($where)
            ->field($field)
            ->order($order)
            ->column($column);
        return $data;
    }
}
