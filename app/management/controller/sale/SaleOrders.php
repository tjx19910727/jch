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

    public function queryRefund()
    {
        $postData = input();
        return $this->app->saleOrders->queryRefund();
    }
}