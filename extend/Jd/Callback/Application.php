<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 15:41
 */

namespace Jd\Callback;


use Jd\Callback\Notify\NotifyClient;
use Jd\Callback\Notify\NotifyProvider;
use Jd\Kernel\ServiceContainer;

/**
 * Class Application
 * @property NotifyClient  $notify  回调处理
 * @package Jd\Callback
 */
class Application extends ServiceContainer
{
    protected $providers = [
        NotifyProvider::class,
    ];

}