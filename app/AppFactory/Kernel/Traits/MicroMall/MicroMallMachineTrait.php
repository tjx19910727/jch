<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/10
 * Time: 10:22
 */

namespace app\AppFactory\Kernel\Traits\MicroMall;



use app\AppFactory\Kernel\Model\MicroMall\MicroMallMachineModel;

trait MicroMallMachineTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getMicroMallMachineValue($where, $value)
    {
        return MicroMallMachineModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getMicroMallMachineColumn($where, $column)
    {
        return MicroMallMachineModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getMicroMallMachineCount($where)
    {
        return MicroMallMachineModel::getCount($where);
    }

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     */
    public function getMicroMallMachineList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return MicroMallMachineModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getMicroMallMachineFind($where, $field = "*", $order = "")
    {
        return MicroMallMachineModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addMicroMallMachine($insert)
    {
        $data = MicroMallMachineModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return MicroMallMachineModel
     */
    public function updateMicroMallMachine($update,$where = [],$field = [])
    {
        return MicroMallMachineModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delMicroMallMachine($where)
    {
        return MicroMallMachineModel::whereDel($where);
    }
}