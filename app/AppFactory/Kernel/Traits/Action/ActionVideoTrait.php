<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/11/11
 * Time: 11:29
 */

namespace app\AppFactory\Kernel\Traits\Action;


use app\AppFactory\Kernel\Model\Action\ActionVideoModel;

trait ActionVideoTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getActionVideoValue($where, $value)
    {
        return ActionVideoModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getActionVideoColumn($where, $column)
    {
        return ActionVideoModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getActionVideoCount($where)
    {
        return ActionVideoModel::getCount($where);
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
    public function getActionVideoList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return ActionVideoModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getActionVideoFind($where, $field = "*", $order = "")
    {
        return ActionVideoModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addActionVideo($insert)
    {
        if (isset($this->manager['manager_id']) && !isset($insert['creator'])) $insert['creator'] = $this->manager['manager_id'];
        if (isset($this->manager['ao_id']) && !isset($insert['ao_id'])) $insert['ao_id'] = $this->manager['ao_id'];
        $data = ActionVideoModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return ActionVideoModel
     */
    public function updateActionVideo($update,$where = [],$field = [])
    {
        if (isset($this->manager['manager_id'])) $update['update_id'] = $this->manager['manager_id'];
        return ActionVideoModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delActionVideo($where)
    {
        return ActionVideoModel::whereDel($where);
    }
}