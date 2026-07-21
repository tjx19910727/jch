<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/22
 * Time: 9:56
 */

namespace app\AppFactory\Wx;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Wx\Official\OfficialProvider;

/**
 * Class Application
 * @property Official\OfficialClient        $official    公众号
 * @property Official\LoginClient           $login       用户登录
 * @property Official\CouponClient          $coupon      优惠券链接领取
 * @package app\AppFactory\Wx
 */
class Application extends ServiceContainer
{
    protected $providers = [
        OfficialProvider::class,
    ];
}