<?php
/**
 * 工厂入口
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/16
 * Time: 16:04
 */

namespace app\AppFactory;

/**
 * Class AppFactory
 * @method static Api\Application              api($config = [])               对外API接口
 * @method static Management\Application       management($user = [])     管理端
 * @method static Machine\Application          machine($config = [])      终端通讯
 * @method static Pay\Application              pay($config = [])          支付
 * @method static TimeTask\Application         timeTask($config = [])     定时任务
 * @method static Mobile\Application           mobile($config = [])       手机端
 * @method static Notice\Application           notice($config = [])       消息通知
 * @method static Wx\Application               wx()                       微信处理
 * @package app\AppFactory
 */
class AppFactory
{

    /**
     * @param $name
     * @param $config
     * @return mixed
     */
    public static function make($name, $config = [])
    {
        $name = ucwords($name);
        $application = "\\app\\AppFactory\\{$name}\\Application";
        return new $application($config);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments = [])
    {
        return self::make($name, ...$arguments);
    }

}