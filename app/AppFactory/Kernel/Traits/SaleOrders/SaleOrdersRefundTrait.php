<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/14
 * Time: 14:06
 */

namespace app\AppFactory\Kernel\Traits\SaleOrders;


use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersRefundModel;

trait SaleOrdersRefundTrait
{
    public function getSaleOrdersRefundSum($where,$sum)
    {
        return SaleOrdersRefundModel::getSum($where,$sum);
    }

    public function getSaleOrdersRefundFind($where,$field = "*")
    {
        return SaleOrdersRefundModel::getFind($where,$field);
    }

    public function getSaleOrdersRefundList($where,$pageNum = 0, $field = "*", $order = "sor_id desc")
    {
        return SaleOrdersRefundModel::getList($where,$pageNum,$field,$order);
    }

    public function getSaleOrdersRefundListJoinSoSod($where,$pageNum = 0,$field = "*",$order = "")
    {
        return SaleOrdersRefundModel::getRefundListJoinSoSod($where,$pageNum,$field,$order);
    }

    public function getSaleOrdersRefundListJoinSo($where,$pageNum = 0,$field = "*",$order = "")
    {
        return SaleOrdersRefundModel::getRefundListJoinSo($where,$pageNum,$field,$order);
    }

    public function addSaleOrdersRefund($insert)
    {
        $insert = $this->normalizeSaleOrderRefundNonNegativeFields($insert);
        !isset($this->manager['manager_id']) ? : $insert['creator'] = $this->manager['manager_id'];
        $sor = SaleOrdersRefundModel::create($insert);
        actionLog($this->getLS(),'生成退款记录结果');
        return $sor->sor_id;
    }

    public function updateSaleOrdersRefund($update, $where = [], $field = [])
    {
        $update = $this->normalizeSaleOrderRefundNonNegativeFields($update);
        return SaleOrdersRefundModel::update($update,$where,$field);
    }

    /**
     * 退款金额与数量不得以负值落库。
     */
    protected function normalizeSaleOrderRefundNonNegativeFields($data)
    {
        if (is_object($data)) {
            $data = method_exists($data, 'toArray') ? $data->toArray() : (array)$data;
        }
        if (!is_array($data)) return $data;

        $fields = ['refund_amount', 'refund_quantity', 'refund_points', 'refund_cost_points'];
        foreach ($fields as $field) {
            if (array_key_exists($field, $data) && is_numeric($data[$field])
                && bccomp(strval($data[$field]), '0', 4) < 0) {
                $data[$field] = '0.0000';
            }
        }
        return $data;
    }

    /**
     * 删除退款记录
     * @param $where
     * @return bool
     */
    public function delSaleOrdersRefund($where)
    {
        return SaleOrdersRefundModel::whereDel($where);
    }


    public function getRefundNo($msg = "")
    {
        while(1) {
            $trade_no = "refund" . date("YmdHis") . ($msg ? $msg : $this->get_rand_string(6));
            if (!SaleOrdersRefundModel::be(['refund_trade_no' => $trade_no])) {
                return $trade_no;
            }
        }
    }
}
