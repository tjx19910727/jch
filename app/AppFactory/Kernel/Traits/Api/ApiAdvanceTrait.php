<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/19
 * Time: 17:30
 */

namespace app\AppFactory\Kernel\Traits\Api;


use app\AppFactory\Kernel\Model\Api\ApiAdvanceModel;

trait ApiAdvanceTrait
{
    /**
     * 获取字段值
     * @param $where
     * @param $value
     * @return mixed
     */
    public function getApiAdvanceValue($where, $value)
    {
        return ApiAdvanceModel::getFieldValue($where, $value);
    }

    /**
     * 获取单列
     * @param $where
     * @param $column
     * @return array
     */
    public function getApiAdvanceColumn($where, $column)
    {
        return ApiAdvanceModel::getColumn($where, $column);
    }

    /**
     * 统计数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     */
    public function getApiAdvanceCount($where)
    {
        return ApiAdvanceModel::getCount($where);
    }

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     */
    public function getApiAdvanceList($where, $pageNum = 0, $field = "*", $order = "")
    {
        return ApiAdvanceModel::getList($where, $pageNum, $field, $order);
    }

    /**
     * 获取一条数据
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getApiAdvanceFind($where, $field = "*", $order = "")
    {
        return ApiAdvanceModel::getFind($where, $field, $order);
    }

    /**
     * 添加
     * @param $insert
     * @return mixed
     */
    public function addApiAdvance($insert)
    {
        $data = ApiAdvanceModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    /**
     * 对外API预订商品生成预订商品记录
     * @return mixed
     */
    protected function createAdvance()
    {
        $insertAdvance = [
            "order_id" => $this->order['order_id'],
            "trade_no" => $this->order['trade_no'],
            "m_id" => $this->machine['m_id'],
            "machine_id" => $this->machine['machine_id'],
            "machine_name" => $this->machine['machine_name'],
            "apc_id" => $this->order['apc_id'],
            "pick_code" => $this->order['pay_code'],
            "payment_method" => $this->config["params"]['payment_method'],
            "customer_name" => $this->config["params"]['customer_name'] ?? "",
            "expire_time" => $this->config["params"]['expire_time'],
            "charge_time" => $this->config["params"]['charge_time'],
            "notify_url" => $this->config["params"]['notify_url'] ?? "",
            "order_detail" => $this->config["params"]['order_detail'],
            "charge_amount" => $this->order["total_price"],
            "quantity" => $this->order['total_quantity'],
            "total_amount" => bcadd($this->order["total_price"],$this->order['discount_price'],2),
            "discount_amount" => $this->order["discount_price"],
            "status" => "OPEN",
        ];
        $aa_id = $this->addApiAdvance($insertAdvance);
        if (!$aa_id) {
            actionLog($this->getLS(),'生成预订商品记录失败');
            return $this->returnData(99,$this->lang("msg.99"));
        }
        return 1;
    }

    /**
     * 修改
     * @param $update
     * @param array $where
     * @param array $field
     * @return ApiAdvanceModel
     */
    public function updateApiAdvance($update,$where = [],$field = [])
    {
        return ApiAdvanceModel::update($update,$where,$field);
    }

    /**
     * 删除
     * @param $where
     * @return mixed
     */
    public function delApiAdvance($where)
    {
        return ApiAdvanceModel::whereDel($where);
    }
}