<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/30
 * Time: 16:58
 */

namespace app\AppFactory\Kernel\Traits\Machine;



use app\AppFactory\Kernel\Model\Machine\MachineErrorCodeSolutionModel;

trait MachineErrorCodeSolutionTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getMachineErrorCodeSolutionValue($where, $value)
    {
        return MachineErrorCodeSolutionModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getMachineErrorCodeSolutionColumn($where, $column)
    {
        return MachineErrorCodeSolutionModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getMachineErrorCodeSolutionCount($where)
    {
        return MachineErrorCodeSolutionModel::getCount($where);
    }

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     */
    public function getMachineErrorCodeSolutionList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return MachineErrorCodeSolutionModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getMachineErrorCodeSolutionFind($where, $field = "*", $order = "")
    {
        return MachineErrorCodeSolutionModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addMachineErrorCodeSolution($insert)
    {
        if (!isset($insert['creator']) && $this->manager['manager_id']) $insert['creator'] = $this->manager['manager_id'];
        $data = MachineErrorCodeSolutionModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return MachineErrorCodeSolutionModel
     */
    public function updateMachineErrorCodeSolution($update,$where = [],$field = [])
    {
        if (!isset($update['update_id']) && $this->manager['manager_id']) $update['update_id'] = $this->manager['manager_id'];
        return MachineErrorCodeSolutionModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delMachineErrorCodeSolution($where)
    {
        return MachineErrorCodeSolutionModel::whereDel($where);
    }
}