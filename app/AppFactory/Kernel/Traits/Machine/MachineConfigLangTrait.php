<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/5/15
 * Time: 15:12
 */

namespace app\AppFactory\Kernel\Traits\Machine;



use app\AppFactory\Kernel\Model\Machine\MachineConfigLangModel;

trait MachineConfigLangTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getMachineConfigLangValue($where, $value)
    {
        return MachineConfigLangModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getMachineConfigLangColumn($where, $column)
    {
        return MachineConfigLangModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getMachineConfigLangCount($where)
    {
        return MachineConfigLangModel::getCount($where);
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
    public function getMachineConfigLangList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return MachineConfigLangModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getMachineConfigLangFind($where, $field = "*", $order = "")
    {
        return MachineConfigLangModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addMachineConfigLang($insert)
    {
        $data = MachineConfigLangModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return MachineConfigLangModel
     */
    public function updateMachineConfigLang($update,$where = [],$field = [])
    {
        return MachineConfigLangModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delMachineConfigLang($where)
    {
        return MachineConfigLangModel::whereDel($where);
    }
}