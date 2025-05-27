<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 10:54
 */

namespace Jd\Declares\Settle;


use Jd\Kernel\BaseClient;

class SettleClient extends BaseClient
{
    /**
     * 创建结算信息
     * 创建结算信息接口，即为设置结算账户，此接口同时开通支付产品以及交易费率信息。
     * @param $params
     * @return bool|string
     */
    public function create($params)
    {
        $url = '/v2/agent/declare/settleinfo/create';
        return $this->httpPost($url,$params);
    }

    /**
     * 修改结算信息
     * @param $params
     * @return bool|string
     */
    public function modify($params)
    {
        $url = '/v2/agent/declare/settleinfo/modify';
        return $this->httpPost($url,$params);
    }

    /**
     * 查询结算信息
     * @param string $customerNum
     * @return bool|string
     */
    public function query(string $customerNum)
    {
        $url = "/v1/agent/declare/settleinfo/$customerNum";
        return $this->httpGet($url);
    }
}