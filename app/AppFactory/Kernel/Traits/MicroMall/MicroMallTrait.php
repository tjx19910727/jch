<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/9
 * Time: 14:48
 */

namespace app\AppFactory\Kernel\Traits\MicroMall;



use app\AppFactory\Kernel\Model\MicroMall\MicroMallModel;

trait MicroMallTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getMicroMallValue($where, $value)
    {
        return MicroMallModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getMicroMallColumn($where, $column)
    {
        return MicroMallModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getMicroMallCount($where)
    {
        return MicroMallModel::getCount($where);
    }

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     */
    public function getMicroMallList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return MicroMallModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getMicroMallFind($where, $field = "*", $order = "")
    {
        return MicroMallModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addMicroMall($insert)
    {
        if (!isset($insert['creator']) && $this->manager['manager_id'])  $insert['creator'] = $this->manager['manager_id'];
        $insert['ao_id'] = $this->manager['manager_id'] ?? 0;
        $data = MicroMallModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return MicroMallModel
     */
    public function updateMicroMall($update,$where = [],$field = [])
    {
        if (!isset($update['update_id']) && $this->manager['manager_id'])  $update['update_id'] = $this->manager['manager_id'];
        return MicroMallModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delMicroMall($where)
    {
        return MicroMallModel::whereDel($where);
    }
}