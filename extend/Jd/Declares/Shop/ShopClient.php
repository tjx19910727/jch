<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 11:01
 */

namespace Jd\Declares\Shop;


use Jd\Kernel\BaseClient;

class ShopClient extends BaseClient
{
    /**
     * 创建店铺
     * @param $params
     * @return bool|string
     */
    public function create($params)
    {
        $url = '/v1/agent/declare/shopinfo/create';
        return $this->httpPost($url,$params);
    }

    /**
     * 修改店铺
     * @param $params
     * @return bool|string
     */
    public function modify($params)
    {
        $url = '/v1/agent/declare/shopinfo/modify';
        return $this->httpPost($url,$params);
    }

    /**
     * 查询店铺信息
     * @param $customerNum
     * @return bool|string
     */
    public function query($customerNum)
    {
        $url = "/v1/agent/declare/shop/list/$customerNum";
        return $this->httpGet($url);
    }
}