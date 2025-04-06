<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/1/14
 * Time: 14:51
 */

namespace AliPay\user;


use AliPay\Kernel\BaseClient;

class InfoClient extends BaseClient
{
    /**
     * 支付宝会员授权信息查询接口
     * @return mixed
     */
    public function share($token)
    {
        $this->onceName = "AlipayUserInfoShareRequest";
        $request = $this->newRequest();
        $this->AopCert($this->config);
        return $this->returnExecute($this->aop->execute($request,$token),$request);
    }
}