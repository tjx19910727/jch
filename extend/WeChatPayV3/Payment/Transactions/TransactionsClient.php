<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/19
 * Time: 14:43
 */

namespace WeChatPayV3\Payment\Transactions;


use WeChatPayV3\Payment\Kernel\BaseClient;

class TransactionsClient extends BaseClient
{
    /**
     * JSAPI支付
     * @param $params
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function jsapi($params)
    {
        return $this->httpPost('/v3/pay/transactions/jsapi', $params);
    }

    /**
     * Native支付
     * @param $params
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function native($params)
    {
        return $this->httpPost('/v3/pay/transactions/native', $params);
    }
}