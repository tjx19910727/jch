<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/5/15
 * Time: 15:10
 */

namespace app\AppFactory\Kernel\Traits\Machine;



use app\AppFactory\Kernel\Model\Machine\MachineLangModel;

trait MachineLangTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getMachineLangValue($where, $value)
    {
        return MachineLangModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getMachineLangColumn($where, $column)
    {
        return MachineLangModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getMachineLangCount($where)
    {
        return MachineLangModel::getCount($where);
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
    public function getMachineLangList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return MachineLangModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getMachineLangFind($where, $field = "*", $order = "")
    {
        return MachineLangModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addMachineLang($insert)
    {
        if (!isset($insert['manager_id']) || !$insert['manager_id']) $insert['creator'] = $this->manager['manager_id'];
        $data = MachineLangModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return MachineLangModel
     */
    public function updateMachineLang($update,$where = [],$field = [])
    {
        if (!isset($update['update_id']) || !$update['update_id']) $update['update_id'] = $this->manager['manager_id'];
        return MachineLangModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delMachineLang($where)
    {
        return MachineLangModel::whereDel($where);
    }
}