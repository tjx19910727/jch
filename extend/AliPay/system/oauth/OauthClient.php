<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/10/22
 * Time: 14:13
 */

namespace AliPay\system\oauth;


use AliPay\Kernel\BaseClient;

class OauthClient extends BaseClient
{

    /**
     * 获取Token或刷新Token
     * @param $code
     * @param string $grantType
     * @param string $refreshToken
     * @return mixed
     */
    public function Token($code,$grantType = "authorization_code",$refreshToken = "")
    {
        $this->onceName = "AlipaySystemOauthTokenRequest";
        $this->AopCert($this->config);
        $request = $this->newRequest();
        $request->setGrantType($grantType);
        $request->setCode($code);
        if ($grantType == "refresh_token") $request->setRefreshToken($refreshToken);
        return $this->returnExecute( $this->aop->execute($request),$request);
    }

    /**
     * 跳转支付宝授权页面
     * @param $appId
     * @param $redirect_uri
     * @param string $scope  //授权方式(目前只支持auth_userinfo和auth_base两个值)
     */
    public function redirect($appId,$redirect_uri,$scope = 'auth_user')
    {
        //url转义
        $redirect_uri = urlencode($redirect_uri);
        //授权地址
        $authUrl = "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=". $appId ."&scope=". $scope ."&redirect_uri=". $redirect_uri;
        header("location:".$authUrl);
    }

}