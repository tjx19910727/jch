<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/11/16
 * Time: 10:33
 */

namespace app\AppFactory\Kernel\Traits\Wx;



use app\AppFactory\Kernel\Model\Wx\WxOfficialLoginModel;

trait WxOfficialLoginTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getWxOfficialLoginValue($where, $value)
    {
        return WxOfficialLoginModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getWxOfficialLoginColumn($where, $column)
    {
        return WxOfficialLoginModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getWxOfficialLoginCount($where)
    {
        return WxOfficialLoginModel::getCount($where);
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
    public function getWxOfficialLoginList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return WxOfficialLoginModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getWxOfficialLoginFind($where, $field = "*", $order = "")
    {
        return WxOfficialLoginModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addWxOfficialLogin($insert)
    {
        $data = WxOfficialLoginModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return WxOfficialLoginModel
     */
    public function updateWxOfficialLogin($update,$where = [],$field = [])
    {
        return WxOfficialLoginModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delWxOfficialLogin($where)
    {
        return WxOfficialLoginModel::whereDel($where);
    }
}