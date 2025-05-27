<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/5/4
 * Time: 16:38
 */

namespace app\AppFactory\Kernel;

/**
 * 服务供应接口类
 * 各供应类需要继承此接口类
 * Interface ServiceProviderInterface
 * @package app\appFactory\Kernel
 */
interface ServiceProviderInterface
{
    public function register(Container $app);
}