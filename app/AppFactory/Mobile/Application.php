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
        $key = env("api.md5Key");
        if (SignUtil::checkSign($postData,$key) !== true)
            return returnState(100,'验签失败');
        $token_arr = [
            "timestamp" => time(),
            "machine_id" => $postData['machine_id'],
            "manager_id" => $postData['manager_id'],
        ];
        $token = TDESUtil::encrypt(json_encode($token_arr),$key);
        return returnState(200,'验证成功',['token' => $token]);
    }
}