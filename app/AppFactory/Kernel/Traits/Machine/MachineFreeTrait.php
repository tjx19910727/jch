<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/26
 * Time: 15:34
 */

namespace app\AppFactory\Kernel\Traits\Machine;



use app\AppFactory\Kernel\Model\Machine\MachineFreeModel;

trait MachineFreeTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getMachineFreeValue($where, $value)
    {
        return MachineFreeModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getMachineFreeColumn($where, $column)
    {
        return MachineFreeModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getMachineFreeCount($where)
    {
        return MachineFreeModel::getCount($where);
    }

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function getMachineFreeList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return MachineFreeModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getMachineFreeFind($where, $field = "*", $order = "")
    {
        return MachineFreeModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addMachineFree($insert)
    {
        $data = MachineFreeModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return MachineFreeModel
     */
    public function updateMachineFree($update,$where = [],$field = [])
    {
        return MachineFreeModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delMachineFree($where)
    {
        return MachineFreeModel::whereDel($where);
    }
}