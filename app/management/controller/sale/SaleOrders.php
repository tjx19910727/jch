<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/5
 * Time: 14:24
 */

namespace app\management\controller\sale;


use app\AppFactory\AppFactory;
use app\management\controller\Common;

class SaleOrders extends Common
{
    protected $validatePath = 'app\management\validate\VSaleOrders.';

    /**
     * 查询订单列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData,false,['trade_no' => "like","mch_no" => "like","machine_name" => "like","machine_id" => "like"]);
        $where['pay_status'] = 3;
        $field = "*";
        return $this->app->saleOrders->getList($where,$pageNum,$field,"order_id desc");
    }

    /**
     * 查询订单详情
     * @return array|string
     * @throws \Exception
     */
    public function getDetails()
    {
        $order_id = input('order_id');
        $where['order_id'] = $order_id;
        $order = $this->app->saleOrders->getSaleOrdersFind($where,'*');
        if ($order) {
            $order['details'] = $this->app->saleOrders->getSaleOrdersDetailsList($where,0,'*','sod_id desc');
        }
        $order = $order->getData();
        return returnData($order);
    }

    /**
     * 商品交易列表
     * @return array|string
     */
    public function getDetailsList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,["g_name" => "like","sku" => 'like',"machine_id" => 'like',"machine_name" => 'like']);
        $where['so.pay_status'] = 3;
        $field = "so.machine_id,so.machine_name,so.trade_no,so.transaction_video,so.order_type,so.pay_type,so.pay_method,so.pay_time,so.out_time,so.create_time,so.out_status,
        sod.sku,sod.g_name,sod.channel_code,sod.retail_price,sod.discount_price,sod.total_sod_price,
        sod.success_quantity,sod.fail_quantity,sod.deliver_pics";
        return returnData($this->app->saleOrders->getSaleOrdersDetailsJoinOrderList($where,($postData['pageNum'] ?? 0),$field,"sod_id desc"));
    }

    /**
     * 导出商品交易
     * @return array|string
     */
    public function exportGoodsList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,["g_name" => "like","sku" => 'like',"machine_id" => 'like',"machine_name" => 'like']);
        $where['so.pay_status'] = 3;
        return $this->app->saleOrders->exportGoodsSo($where);
    }

    /**
     * 订单退款
     * @return array|bool|string
     */
    public function refund()
    {
        $postData = input();
        actionLog($postData,'退款数据');
        try { $this->validate($postData,$this->validatePath . 'refund');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $postData['refund'] = json2arr($postData['refund']);
        return $this->app->saleOrders->refundOrder($postData);
    }

    /**
     * 下发获取交易视频
     * @return array|string
     */
    public function getTransactionVideo()
    {
        $trade_no = input('trade_no');
        $order = $this->app->saleOrders->getFind(['trade_no' => $trade_no],'transaction_video,machine_id','',0);
        if (!$order) return returnState(100,lang("VSaleOrders.order_no_data"));
        if (!$order['transaction_video']) {
            $config = [
                "machine_id" => $order['machine_id'],
                "key" => env("api.md5Key"),
            ];
            $app = AppFactory::machine($config);
            $app->sendMq->getTransactionVideo($trade_no);
            return returnState(200,'正在从机器端获取视频文件，请稍做等待后下载');
        }
        return returnState(200,'查询成功',$order);
    }

    /**
     * 导出订单列表信息
     * @return array|string
     * @throws \Exception
     */
    public function export()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,["order_id" => "in",'trade_no' => "like","mch_no" => "like","machine_name" => "like","machine_id" => "like"]);
        $where['pay_status'] = 3;
        return $this->app->saleOrders->exportSo($where);
    }

    /**
     * 获取订单退款列表
     * @return array|string
     */
    public function getRefundList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->authNodeWhere();
        if (isset($postData['trade_no'])) $where[] = ['sor.trade_no','like',"%" .$postData['trade_no']. "%"];
        if (isset($postData['machine_id'])) $where[] = ['sor.machine_id','like',"%" .$postData['machine_id']. "%"];
        if (isset($postData['refund_no'])) $where[] = ['sor.refund_no','like',"%" .$postData['refund_no']. "%"];
        if (isset($postData['pay_type'])) $where['pay_type'] = $postData['pay_type'];
//        $where = $this->getWhere($postData,false,['refund_trade_no' => "like",'machine_id' => "like",'trade_no' => "like","refund_no" => "like"]);
        $field = "sor.*,so.pay_type";
        return returnData($this->app->saleOrders->getSaleOrdersRefundListJoinSoSod($where,$pageNum,$field,'sor.sor_id desc'));
    }

    /**
     * 导出退款记录列表
     * @return array|string
     */
    public function exportRefund()
    {
        $postData = input();
        $where = $this->authNodeWhere();
        if (isset($postData['trade_no'])) $where[] = ['sor.trade_no','like',"%" .$postData['trade_no']. "%"];
        if (isset($postData['machine_id'])) $where[] = ['sor.machine_id','like',"%" .$postData['machine_id']. "%"];
        if (isset($postData['refund_no'])) $where[] = ['sor.refund_no','like',"%" .$postData['refund_no']. "%"];
        if (isset($postData['pay_type'])) $where['pay_type'] = $postData['pay_type'];
//        $where = $this->getWhere($postData,false,['refund_trade_no' => "like",'machine_id' => "like",'trade_no' => "like","refund_no" => "like"]);
        return $this->app->saleOrders->exportRefund($where);
    }

    /**
     * 获取销售报表概况
     * @return array|string
     */
    public function getReport()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,["machine_id" => "like"]);
        $field = "ao_name,
        SUM(order_num) order_num,
        sum(totalRefundAmount) totalRefundAmount,
        SUM(totalRefundQuantity) totalRefundQuantity,
        SUM(totalPrice) totalPrice,
        SUM(totalDiscountPrice) totalDiscountPrice,
        SUM(totalQuantity) totalQuantity,
        SUM(giftQuantity) giftQuantity,
        SUM(coupon_used) coupon_used,
        SUM(lottery_used) lottery_used,
        SUM(lotteryAmount) lotteryAmount,
        SUM(lotteryQuantity) lotteryQuantity";
        if (isset($postData['machine_id'])) $field = "machine_id,machine_name," . $field;
        return $this->app->saleOrders->getTotalReport($where,$field,'countDate desc');
    }

    /**
     * 获取销售报表
     * @return array|string
     */
    public function getReportList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $order = "create_date desc";
        if (isset($postData['order'])) {
            $order = $postData['order'];
            unset($postData['order']);
        }
        $where = $this->getWhere($postData,false,["machine_id" => "like"]);
        return $this->app->saleOrders->getReportList($where,$pageNum,$order);
    }

    /**
     * 导出销售报表
     * @return array|string
     */
    public function exportReport()
    {
        $postData = input();
        $order = "create_date desc";
        if (isset($postData['order'])) {
            $order = $postData['order'];
            unset($postData['order']);
        }
        $where = $this->getWhere($postData,false,["machine_id" => "like"]);
        return $this->app->saleOrders->exportReport($where,$order);
    }
}