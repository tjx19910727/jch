<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 11:43
 */

namespace Jd\Declares;


use Jd\Kernel\BaseClient;

class DeclareClient extends BaseClient
{
    /**
     * 提交报单
     * 在创建完商户信息、结算信息、店铺信息和上传附件之后，调用提交报单接口完成商户入网。
     * @param $params
     * @return bool|string
     */
    public function complete($params)
    {
        $url = '/v2/agent/declare/complete';
        return $this->httpPost($url,$params);
    }

    /**
     * 报单确认
     * 提交报单之后即可调用报单确认接口，否则会影响商户交易。
     * @param $params
     * @return bool|string
     */
    public function signConfirm($params)
    {
        $url = '/v1/agent/declare/sign/confirm';
        return $this->httpPost($url,$params);
    }

    /**
     * 报单结果查询
     * 通过此接口可查询商户审核状态。
     * @param $params
     * @return bool|string
     */
    public function query($params)
    {
        $url = '/v1/agent/declare/list';
        return $this->httpPost($url,$params);
    }
}