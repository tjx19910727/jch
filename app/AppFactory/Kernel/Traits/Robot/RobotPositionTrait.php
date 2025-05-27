<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/3/18
 * Time: 14:15
 */

namespace app\AppFactory\Kernel\Traits\Robot;




use app\AppFactory\Kernel\Model\Robot\RobotPositionModel;

trait RobotPositionTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getRobotPositionValue($where, $value)
    {
        return RobotPositionModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getRobotPositionColumn($where, $column)
    {
        return RobotPositionModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getRobotPositionCount($where)
    {
        return RobotPositionModel::getCount($where);
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
    public function getRobotPositionList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return RobotPositionModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getRobotPositionFind($where, $field = "*", $order = "")
    {
        return RobotPositionModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addRobotPosition($insert)
    {
        $data = RobotPositionModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return RobotPositionModel
     */
    public function updateRobotPosition($update,$where = [],$field = [])
    {
        return RobotPositionModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delRobotPosition($where)
    {
        return RobotPositionModel::whereDel($where);
    }
}