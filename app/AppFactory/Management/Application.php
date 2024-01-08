<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:09
 */

namespace app\AppFactory\Management;


use app\AppFactory\Kernel\Providers\Management\ActivityProvider;
use app\AppFactory\Kernel\Providers\Management\AdvertisementProvider;
use app\AppFactory\Kernel\Providers\Management\AttendedProvider;
use app\AppFactory\Kernel\Providers\Management\AuthProvider;
use app\AppFactory\Kernel\Providers\Management\CommonProvider;
use app\AppFactory\Kernel\Providers\Management\ConfigProvider;
use app\AppFactory\Kernel\Providers\Management\FluoriteProvider;
use app\AppFactory\Kernel\Providers\Management\GoodsProvider;
use app\AppFactory\Kernel\Providers\Management\IndexProvider;
use app\AppFactory\Kernel\Providers\Management\LoginProvider;
use app\AppFactory\Kernel\Providers\Management\OpenPlatformProvider;
use app\AppFactory\Kernel\Providers\Management\SaleOrdersProvider;
use app\AppFactory\Kernel\Providers\Management\StoreProvider;
use app\AppFactory\Kernel\Providers\Management\StrategyProvider;
use app\AppFactory\Kernel\Providers\Management\UserProvider;
use app\AppFactory\Kernel\Providers\Management\WarehouseProvider;
use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Config\ConfigTrait;

/**
 * Class Application
 * @property Login\LoginClient                      $login                  登录
 *
 * @property Common\CityClient                      $city                   城市
 *
 *
 * @property Index\SaleDataClient                   $saleData               销售数据
 * @property Index\StoreDataClient                  $storeData              门店数据
 * @property Index\CargoDamageDataClient            $cargoDamageData        货损数据
 * @property Index\TodoClient                       $todo                   待办事项
 *
 * @property Activity\ActivityDiscountClient                $activityDiscount       即时折扣
 * @property Activity\ActivityFullDecClient                 $activityFullDec        满减活动
 * @property Activity\ActivityHgClient                      $activityHg             加价换购活动
 * @property Activity\ActivityHgGoodsClient                 $activityHgGoods        加价换购活动内容
 * @property Activity\ActivityTimeClient                    $activityTime           活动时间
 * @property Activity\ActivityGoodsClient                   $activityGoods          活动商品
 *
 * @property Advertisement\AdvertisementPushClient          $advertisementPush      广告推送
 * @property Advertisement\AdvertisementResourceClient      $advertisementResource  广告素材
 *
 * @property Auth\AuthManagerRoleClient             $authManagerRole        管理员绑定角色
 * @property Auth\AuthManagerClient                 $authManager            管理员
 * @property Auth\AuthNodeClient                    $authNode               权限节点
 * @property Auth\AuthRoleClient                    $authRole               权限角色
 * @property Auth\AuthRoleNodeClient                $authRoleNode           权限角色绑定权限节点
 * @property Auth\AuthOrganizationClient            $authOrganization       组织架构
 * @property Auth\AuthOrganizationRoleClient        $authOrganizationRole   组织架构关联权限角色
 *
 * @property Config\ConfigClient                    $config                 系统配置
 *
 *
 * @property Goods\GoodsClient                      $goods                  商品信息
 * @property Goods\GoodsCategoryClient              $goodsCategory          商品分类信息
 *
 *
 * @property Sale\SaleOrdersClient                  $saleOrders             销售订单
 *
 * @package app\AppFactory\Management
 */
class Application extends ServiceContainer
{
    use ConfigTrait;

    protected $providers = [
        ActivityProvider::class,
        AdvertisementProvider::class,
        AuthProvider::class,
        IndexProvider::class,
        CommonProvider::class,
        ConfigProvider::class,
        LoginProvider::class,
        GoodsProvider::class,
        SaleOrdersProvider::class,
    ];

}