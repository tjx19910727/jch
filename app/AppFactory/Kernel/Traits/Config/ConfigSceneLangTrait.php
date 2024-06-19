<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/19
 * Time: 10:42
 */

namespace app\AppFactory\Kernel\Traits\Config;




use app\AppFactory\Kernel\Model\Config\ConfigSceneLangModel;

trait ConfigSceneLangTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getConfigSceneLangValue($where, $value)
    {
        return ConfigSceneLangModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getConfigSceneLangColumn($where, $column)
    {
        return ConfigSceneLangModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getConfigSceneLangCount($where)
    {
        return ConfigSceneLangModel::getCount($where);
    }

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     */
    public function getConfigSceneLangList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return ConfigSceneLangModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getConfigSceneLangFind($where, $field = "*", $order = "")
    {
        return ConfigSceneLangModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addConfigSceneLang($insert)
    {
        $data = ConfigSceneLangModel::create($insert);
        return $data->getLastInsID();
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return ConfigSceneLangModel
     */
    public function updateConfigSceneLang($update,$where = [],$field = [])
    {
        return ConfigSceneLangModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delConfigSceneLang($where)
    {
        return ConfigSceneLangModel::whereDel($where);
    }
}