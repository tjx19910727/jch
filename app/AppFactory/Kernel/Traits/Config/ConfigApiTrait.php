<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 16:24
 */

namespace app\AppFactory\Kernel\Traits\Config;



use app\AppFactory\Kernel\Model\Config\ConfigApiModel;

trait ConfigApiTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getConfigApiValue($where, $value)
    {
        return ConfigApiModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getConfigApiColumn($where, $column)
    {
        return ConfigApiModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getConfigApiCount($where)
    {
        return ConfigApiModel::getCount($where);
    }

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     */
    public function getConfigApiList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return ConfigApiModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getConfigApiFind($where, $field = "*", $order = "")
    {
        return ConfigApiModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addConfigApi($insert)
    {
        $insert['creator'] = $this->manager['manager_id'] ?? 0;
        $insert['ao_id'] = $this->manager['ao_id'] ?? 0;
        $data = ConfigApiModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return ConfigApiModel
     */
    public function updateConfigApi($update,$where = [],$field = [])
    {
        $update['update_id'] = $this->manager['manager_id'] ?? 0;
        return ConfigApiModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delConfigApi($where)
    {
        return ConfigApiModel::whereDel($where);
    }
}