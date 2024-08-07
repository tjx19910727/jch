<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/2
 * Time: 17:16
 */

namespace app\AppFactory\Kernel\Support\Trip;

/**
 * Class Order
 * @package app\AppFactory\Kernel\Support\Trip
 */
class Order extends Common
{
    public function __construct()
    {
        parent::__construct();
        $this->getToken();
    }

    /**
     * 获取丽呈小程序支付二维码，mallOrderInfo,roomOrderInfo
     * @param $params
     * @return array|bool|string
     */
    public function create($params)
    {
        $url = "/order/createOrder";
        return $this->requestPost($url,$params);
    }

    /**
     * 出货通知
     * @param $params
     * @return array|bool|string
     */
    public function pick($params)
    {
        $url = "/order/pickNotify";
        return $this->requestPost($url,$params);
    }

    /**
     * 客户线下支付结果通知接口
     * @param $params
     * @return array|bool|string
     */
    public function payNotify($params)
    {
        $url = "/order/roomOrderPayNotify";
        return $this->requestPost($url,$params);
    }

    /**
     * 客房订单可订接口
     * @param $params
     * @return array|bool|string
     */
    public function availableCheck($params)
    {
        $url = "/order/availableCheck";
        return $this->requestPost($url,$params);
    }
}