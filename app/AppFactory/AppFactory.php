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