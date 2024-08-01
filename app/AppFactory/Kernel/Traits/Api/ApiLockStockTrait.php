<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/1
 * Time: 10:08
 */

namespace app\AppFactory\Kernel\Traits\Api;



use app\AppFactory\Kernel\Model\api\ApiLockStockModel;

trait ApiLockStockTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getApiLockStockValue($where, $value)
    {
        return ApiLockStockModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getApiLockStockColumn($where, $column)
    {
        return ApiLockStockModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getApiLockStockCount($where)
    {
        return ApiLockStockModel::getCount($where);
    }

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     */
    public function getApiLockStockList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return ApiLockStockModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getApiLockStockFind($where, $field = "*", $order = "")
    {
        return ApiLockStockModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addApiLockStock($insert)
    {
        $data = ApiLockStockModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return ApiLockStockModel
     */
    public function updateApiLockStock($update,$where = [],$field = [])
    {
        return ApiLockStockModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delApiLockStock($where)
    {
        return ApiLockStockModel::whereDel($where);
    }
}