<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 8:40
 */

namespace app\AppFactory\Mobile;


use app\AppFactory\Kernel\Providers\Mobile\MachineProvider;
use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Util\SignUtil;
use app\AppFactory\Kernel\Util\TDESUtil;

/**
 * Class Application
 * @property Machine\CheckClient                           $machineCheck    设备检查
 * @property Machine\InfoClient                           $machineInfo    设备信息
 * @package app\AppFactory\Mobile
 */
class Application extends ServiceContainer
{
    use MachineTrait;
    protected $providers = [
        MachineProvider::class,
    ];


    /**
     * 检查扫码验签
     * @param $postData
     * @return array|string
     */
    public function checkScan($postData)
    {
        $machine_id = $postData['machine_id'];
        $key = "";
        if (!$key) $key = cache($machine_id . ".signKey");
        if (!$key) $key = $this->getMachineValue(['machine_id' => $machine_id],'signKey');
        if (!$key) $key = env("api.md5Key");
        if (SignUtil::checkSign($postData,$key) !== true)
            return returnState(100,'验签失败');
        $tokenKey = env("api.md5Key");
        $token_arr = [
            "timestamp" => time(),
            "machine_id" => $postData['machine_id'],
            "manager_id" => $postData['manager_id'],
        ];
        $token = TDESUtil::encrypt(json_encode($token_arr),$tokenKey);
        return returnState(200,'验证成功',['token' => $token]);
    }
}