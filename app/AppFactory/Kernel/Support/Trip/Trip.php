<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/3
 * Time: 9:00
 */

namespace app\AppFactory\Kernel\Support\Trip;

/**
 * Class Trip
 * @method static Order      order()
 * @method static Hotel      hotel()
 * @package app\AppFactory\Kernel\Support\Trip
 */
class Trip
{

    public static function __callStatic($name, $arguments)
    {
        // TODO: Implement __callStatic() method.
        $name = ucwords($name);
        $name = "\\app\\AppFactory\\Kernel\\Support\\Trip\\" . $name;
        return new $name($arguments);
    }
}