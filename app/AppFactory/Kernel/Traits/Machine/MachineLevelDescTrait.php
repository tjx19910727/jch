<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:34
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Machine\MachineLevelDescModel;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\RemoteActionLog\RemoteActionLogTrait;
trait MachineLevelDescTrait
{
    use SaleOrdersTrait, RemoteActionLogTrait;
    /**
     * 获取设备指定列数据
     * @param $where
     * @param $column
     * @return array
     */
    public function getMachineLevelColumn($where,$column)
    {
        return MachineLevelDescModel::getColumn($where,$column);
    }

    /**
     * 获取设备字段数值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getMachineLevelValue($where,$value)
    {
        return MachineLevelDescModel::getFieldValue($where,$value);
    }

    public function getMachineLevelCount($where)
    {
        return MachineLevelDescModel::getCount($where);
    }


    /**
     * 获取一条设备等级信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return MachineLevelDescModel|array|mixed|null|\think\Model
     */
    public function getMachineLevelFind($where,$field = "*",$order = "")
    {
        return MachineLevelDescModel::getFind($where,$field,$order);
    }

    /**
     * 获取设备等级列表
     * @param $where
     * @param int|array $pageNum
     * @param string $field
     * @param string $order
     * @param string $eachFun
     * @param string $group
     * @param string $limit
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|\think\Collection|\think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMachineLevelList($where,$pageNum = null,$field = "*", $order = "",$eachFun = "",$group = '', $limit = '')
    {
        $result = MachineLevelDescModel::getList($where,$pageNum,$field,$order,$eachFun,$group,$limit);
        return $result;
    }

    /**
     * 添加设备等级信息
     * @param $insert
     * @return mixed
     */
    public function addMachineLevel($insert)
    {
        $data = MachineLevelDescModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改设备等级信息
     * @param $update
     * @param array $where
     * @param array $field
     * @return MachineLevelDescModel
     */
    public function updateMachineLevel($update,$where = [],$field = [])
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        return MachineLevelDescModel::update($update,$where,$field);
    }

    /**
     * 删除设备等级信息
     * @param $where
     * @return bool
     */
    public function delMachineLevel($where)
    {
        $result = MachineLevelDescModel::whereDel($where);
        return $result;
    }
}