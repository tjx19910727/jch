<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/5
 * Time: 14:24
 */

namespace app\management\controller\sale;


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
        $where = $this->getWhere($postData,false,['trade_no' => "like","mch_no" => "like","store_name" => "like","terminal_no" => "like"]);
        $where['payment_status'] = 2;
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
     * 生成补扣订单
     * @return array|string
     * @throws \Exception
     */
    public function supplementary()
    {
        $postData = input();
        $postData = json2arr($postData);
        try { $this->validate($postData,$this->validatePath . 'supplementary');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->saleOrders->supplementary($postData);
    }

    /**
     * 获取一条补扣订单信息
     * @return array|string
     * @throws \Exception
     */
    public function getSupplementaryFind()
    {
        $postData = input();
        $postData = json2arr($postData);
        $where = $this->getWhere($postData);
        return $this->app->saleOrders->getSupplementaryFind($where);
    }

    /**
     * 获取一条监控数据
     * @return array|string
     */
    public function getVideo()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->saleOrders->getVideo($where);
    }

    /**
     * 保存补扣订单视频数据
     * @return array|string
     */
    public function saveOrderVideo()
    {
        $postData = input();
        $postData = json2arr($postData);
        try { $this->validate($postData,$this->validatePath . 'saveVideo');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->saleOrders->saveVideo($postData);
    }

    /**
     * 发送补扣订单消息通知
     * @return array|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendSupplementaryNotice()
    {
        $postData = input();
        return $this->app->saleOrders->sendSupplementaryNotice($postData);
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
        return $this->app->saleOrders->refundOrder($postData);
    }
}