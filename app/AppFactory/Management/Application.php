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
use app\AppFactory\Kernel\Providers\Management\AuthProvider;
use app\AppFactory\Kernel\Providers\Management\CommonProvider;
use app\AppFactory\Kernel\Providers\Management\ConfigProvider;
use app\AppFactory\Kernel\Providers\Management\EarthProvider;
use app\AppFactory\Kernel\Providers\Management\GoodsProvider;
use app\AppFactory\Kernel\Providers\Management\IndexProvider;
use app\AppFactory\Kernel\Providers\Management\LoginProvider;
use app\AppFactory\Kernel\Providers\Management\MachineProvider;
use app\AppFactory\Kernel\Providers\Management\ResourceProvider;
use app\AppFactory\Kernel\Providers\Management\SaleOrdersProvider;
use app\AppFactory\Kernel\Providers\Management\StrategyProvider;
use app\AppFactory\Kernel\Providers\Management\TemplateProvider;
use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Config\ConfigTrait;

/**
 * Class Application
 * @property Login\LoginClient                      $login                  登录
 *
 * @property Common\CityClient                      $city                   城市
 *
 *
 *
 * @property Advertisement\AdvertisementPushClient          $advertisementPush      广告推送
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
 * @property Config\ConfigSizeClient                $configSize             尺寸管理
 * @property Config\ConfigSceneClient               $configScene            场景管理
 * @property Config\ConfigLangClient                $configLang             语言管理
 * @property Config\ConfigPerformanceClient         $configPerformance      性能参数配置
 *
 * @property Earth\EarthClient                      $earth                  全球地区信息与时区
 *
 *
 * @property Goods\GoodsClient                      $goods                  商品信息
 * @property Goods\GoodsLangClient                  $goodsLang              商品多语言信息
 * @property Goods\GoodsCategoryClient              $goodsCategory          商品分类信息
 * @property Goods\GoodsCategoryLangClient          $goodsCategoryLang      商品分类语言信息
 *
 * @property Machine\MachineChannelClient           $machineChannel         设备货道
 * @property Machine\MachineClient                  $machine                设备基础信息
 * @property Machine\MachineConfigClient            $machineConfig          设备配置信息
 * @property Machine\MachineGoodsClient             $machineGoods           设备商品信息
 * @property Machine\MachineGroupClient             $machineGroup           设备分组
 * @property Machine\MachineGroupLangClient         $machineGroupLang       设备分组多语言信息
 * @property Machine\MachineHelpClient              $machineHelp            设备帮助
 * @property Machine\MachineInfoClient              $machineInfo            设备信息
 * @property Machine\MachineViewClient              $machineView            设备视图
 *
 * @property Resource\ResourceClient                $resource  广告素材
 *
 * @property Template\TemplateClient                $template               模板
 * @property Template\TemplatePluginsClient         $templatePlugins        模板组件
 * @property Template\TemplateLayoutClient          $templateLayout         模板布局
 * @property Template\TemplateViewClient            $templateView           模板视图
 *
 * @property Sale\SaleOrdersClient                  $saleOrders             销售订单
 *
 * @property Strategy\StrategyIncomeClient          $strategyIncome         分润策略
 * @property Strategy\StrategyMachineClient         $strategyMachine        策略绑定设备
 * @property Strategy\StrategyManagerClient         $strategyManager        策略绑定账号
 * @property Strategy\StrategyPayeeClient           $strategyPayee          收款策略
 *
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
        EarthProvider::class,
        LoginProvider::class,
        MachineProvider::class,
        GoodsProvider::class,
        ResourceProvider::class,
        SaleOrdersProvider::class,
        StrategyProvider::class,
        TemplateProvider::class,
    ];

}