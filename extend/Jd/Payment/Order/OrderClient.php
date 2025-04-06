<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/9
 * Time: 13:59
 */

namespace Jd\Payment\Order;


use Jd\Kernel\BaseClient;

class OrderClient extends BaseClient
{
    /**
     * JSAPI、小程序支付
     * @param array $params
     * @return bool|string
     */
    public function jsApi(array $params)
    {
        $url = '/api/createPayWithCheck';
        return $this->httpPost($url,$params);
    }

    /**
     * 扫码支付-主扫-生成链接，跳转，或将链接生成二维码给消费者扫
     * @param array $params
     * @return bool|string
     */
    public function qrCodeUrl(array $params)
    {
        $url = '/api/generateQRCodeUrl';
        return $this->httpPost($url,$params);
    }

    /**
     * 刷卡支付-被扫
     * @param array $params
     * @return bool|string
     */
    public function microPay(array $params)
    {
        $url = '/api/microPayWithCheck';
        return $this->httpPost($url,$params);
    }

    /**
     * 查询订单
     * @param array $params
     * @return bool|string
     */
    public function queryByTradeNo(array $params)
    {
        $url = '/api/queryOrderByRequestNum';
        return $this->httpPost($url,$params);
    }

    /**
     * 批量查询
     * @param array $params
     * @param string $type customer商户请求查询，agent代理商请求查询
     * @return bool|string
     */
//    public function batchQuery(array $params,$type = 'customer')
//    {
//        $url = "/v1/$type/order/customer/batch/query";
//        return $this->httpPost($url,$params);
//    }

    /**
     * 申请退款
     * @param array $params
     * @return bool|string
     */
    public function refundByTradeNo(array $params)
    {
        $url = '/api/refundByRequestNum';
        return $this->httpPost($url,$params);
    }

    /**
     * 退款查询
     * @param array $params
     * @return bool|string
     */
    public function refundQueryByTradeNo(array $params)
    {
        $url = '/api/queryRefundOrderByRequestNum';
        return $this->httpPost($url,$params);
    }

    /**
     * 撤销订单
     * @param array $params
     * @return bool|string
     */
    public function cancel(array $params)
    {
        $url = "/api/cancel";
        return $this->httpPost($url,$params);
    }

    /**
     * 关闭订单
     * @param array $params
     * @return bool|string
     */
    public function close(array $params)
    {
        $url = "/api/close";
        return $this->httpPost($url,$params);
    }

    /**
     * 下载账单
     * @param array $params
     * @param string $type
     * @return bool|string
     */
    public function downloadFile(array $params,$type = 'customer')
    {
        $url = "/v1/$type/checkaccountfile/download";
        return $this->httpPost($url,$params);
    }
}