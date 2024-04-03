<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 8:41
 */

namespace app\AppFactory\Mobile;


use app\AppFactory\Kernel\BaseClient;
use app\AppFactory\Kernel\Util\SignUtil;
use app\AppFactory\Kernel\Util\TDESUtil;

class MobileBase extends BaseClient
{
    protected $tokenArr;

    /**
     * 检查扫码验签
     * @param $postData
     * @return array|string
     */
    public function checkScan($postData)
    {
        $key = env("api.md5Key");
        if (SignUtil::checkSign($postData,$key) !== true)
            return $this->r(100,'验签失败');
        $token_arr = [
            "timestamp" => time(),
            "machine_id" => $postData['machine_id'],
            "manager_id" => $postData['manager_id'],
        ];
        $token = TDESUtil::encrypt(json_encode($token_arr),$key);
        return $this->r(200,'验证成功',['token' => $token]);
    }

    /**
     * 检查Token
     */
    public function checkToken()
    {
        $token = request()->header("token");
        if (!$token) die(json(['state' => 100,"msg" => '令牌不能为空，请重新登录'])->send());
        $tokenArr = json2arr(TDESUtil::decrypt($token,env("api.md5Key")));
        if ($tokenArr['timestamp'] <= 3600) {
            die(json(['state' => 100,"msg" => "登录超时，请重新扫码登录"])->send());
        }
        $this->tokenArr = $tokenArr;
    }
}