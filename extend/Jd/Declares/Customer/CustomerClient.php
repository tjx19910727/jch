<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 10:48
 */

namespace Jd\Declares\Customer;


use Jd\Kernel\BaseClient;

class CustomerClient extends BaseClient
{

    /**
     * 创建商户
     * @param $params
     * @return bool|string
     */
    public function create($params)
    {
        $url = "/v2/agent/declare/customerinfo/create";
        return $this->httpPost($url,$params);
    }

    /**
     * 修改商户
     * @param $params
     * @return bool|string
     */
    public function modify($params)
    {
        $url = "/v2/agent/declare/customerinfo/modify";
        return $this->httpPost($url,$params);
    }

    /**
     * 商户信息查询
     * @param $code
     * @return bool|string
     */
    public function query($code)
    {
        $url = "/v1/agent/declare/customerinfo/$code";
        return $this->httpGet($url);
    }
}