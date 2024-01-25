<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 9:14
 */

namespace Jd\Base\Agent;


use Jd\Kernel\BaseClient;

class AgentClient extends BaseClient
{
    /**
     * 省份查询
     * @return bool|string
     */
    public function getProvince()
    {
        $url = '/v1/agent/province/list';
        return $this->httpGet($url);
    }

    /**
     * 通过省份code查询城市
     * @param string $provinceCode   省份编号
     * @return bool|string
     */
    public function getCity(string $provinceCode)
    {
        $url = '/v1/agent/city/list/code/' . $provinceCode;
        return $this->httpGet($url);
    }

    /**
     * 通过城市code查询地区
     * @param string $cityCode  城市编号
     * @return bool|string
     */
    public function getDistrict(string $cityCode)
    {
        $url = '/v1/agent/district/list/code/' . $cityCode;
        return $this->httpGet($url);
    }

    /**
     * 行业查询
     * @return bool|string
     */
    public function getFirstIndustry()
    {
        $url = '/v1/agent/industry/list';
        return $this->httpGet($url);
    }

    /**
     * 通过一级行业code查询二级行业
     * @param string $firstCode  一级行业code
     * @return bool|string
     */
    public function getSecondIndustry(string $firstCode)
    {
        $url = '/v1/agent/industry/second/list/code/' . $firstCode;
        return $this->httpGet($url);
    }

    /**
     * 支付产品查询
     * @return bool|string
     */
    public function getBankInfo()
    {
        $url = '/v1/agent/pay/bankinfo/list';
        return $this->httpGet($url);
    }

    /**
     * 通过银行关键字查询银行列表
     * @param string $keyName  银行关键字
     * @return bool|string
     */
    public function getBank(string $keyName)
    {
        $url = '/v1/agent/bank/list/' . $keyName;
        return $this->httpGet($url);
    }

    /**
     * 通过银行code和支付行关键字查询支行
     * @param string $bankCode  银行code
     * @param string $branchKey  支付关键字
     * @return bool|string
     */
    public function getBankSub(string $bankCode,string $branchKey)
    {
        $url = "/v1/agent/bankSub/list/$bankCode/$branchKey";
        return $this->httpGet($url);
    }
}