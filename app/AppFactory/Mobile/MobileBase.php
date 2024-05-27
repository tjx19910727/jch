<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 8:41
 */

namespace app\AppFactory\Mobile;


use app\AppFactory\Kernel\BaseClient;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Util\SignUtil;
use app\AppFactory\Kernel\Util\TDESUtil;

class MobileBase extends BaseClient
{
    use AuthManagerTrait;
    protected $tokenArr;
    protected $manager;

    /**
     * 检查Token
     */
    public function checkToken()
    {
        $token = request()->header("token");
        if (!$token) $token = input("token");
        if (!$token) return $this->r(100,'令牌不能为空，请重新登录');
        $tokenArr = json_decode(TDESUtil::decrypt($token,env("api.md5Key")),true);
        if ($tokenArr['timestamp'] <= 3600) {
            return $this->r( 100,"登录超时，请重新扫码登录");
        }
        $this->tokenArr = $tokenArr;
    }
}