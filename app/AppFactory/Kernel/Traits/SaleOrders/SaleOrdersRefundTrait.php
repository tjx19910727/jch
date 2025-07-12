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
        !isset($this->manager['manager_id']) ? : $insert['creator'] = $this->manager['manager_id'];
        $sor = SaleOrdersRefundModel::create($insert);
        actionLog($this->getLS(),'生成退款记录结果');
        return $sor->sor_id;
    }

    public function updateSaleOrdersRefund($update, $where = [], $field = [])
    {
        return SaleOrdersRefundModel::update($update,$where,$field);
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
            $trade_no = date("YmdHis") . ($msg ? $msg : $this->get_rand_string(6));
            if (!SaleOrdersRefundModel::be(['refund_trade_no' => $trade_no])) {
                return $trade_no;
            }
        }
    }
}