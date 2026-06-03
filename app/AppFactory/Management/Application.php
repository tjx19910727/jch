<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:09
 */

namespace app\AppFactory\Management;


use app\AppFactory\Kernel\Providers\Management\ActionProvider;
use app\AppFactory\Kernel\Providers\Management\ActivityProvider;
use app\AppFactory\Kernel\Providers\Management\AdvertisementProvider;
use app\AppFactory\Kernel\Providers\Management\AuthProvider;
use app\AppFactory\Kernel\Providers\Management\CardProvider;
use app\AppFactory\Kernel\Providers\Management\CommonProvider;
use app\AppFactory\Kernel\Providers\Management\ConfigProvider;
use app\AppFactory\Kernel\Providers\Management\EarthProvider;
use app\AppFactory\Kernel\Providers\Management\EmailProvider;
use app\AppFactory\Kernel\Providers\Management\ExportProvider;
use app\AppFactory\Kernel\Providers\Management\GoodsProvider;
use app\AppFactory\Kernel\Providers\Management\HotelProvider;
use app\AppFactory\Kernel\Providers\Management\IndexProvider;
use app\AppFactory\Kernel\Providers\Management\LoginProvider;
use app\AppFactory\Kernel\Providers\Management\MachineProvider;
use app\AppFactory\Kernel\Providers\Management\MicroMallProvider;
use app\AppFactory\Kernel\Providers\Management\ResourceProvider;
use app\AppFactory\Kernel\Providers\Management\SaleOrdersProvider;
use app\AppFactory\Kernel\Providers\Management\StrategyProvider;
use app\AppFactory\Kernel\Providers\Management\SuggestProvider;
use app\AppFactory\Kernel\Providers\Management\TemplateProvider;
use app\AppFactory\Kernel\Providers\Management\TripProvider;
use app\AppFactory\Kernel\Providers\Management\UpdateLogProvider;
use app\AppFactory\Kernel\Providers\Management\WxProvider;
use app\AppFactory\Kernel\Providers\Management\MallProvider;
use app\AppFactory\Kernel\Providers\Management\RemoteActionLogProvider;
use app\AppFactory\Kernel\Providers\Management\WeiChengProvider;

use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Config\ConfigTrait;

/**
 * Class Application
 * @property Login\LoginClient                      $login                  登录
 * @property Login\LoginV2Client                    $loginV2                登录V2
 *
 * @property Common\CityClient                      $city                   城市
 *
 * @property Action\ActionVideoClient                $actionVideo           操作视频
 *
 * @property Activity\ActivityCouponClient           $activityCoupon        优惠券活动
 * @property Activity\ActivityCouponUsedClient       $activityCouponUsed    优惠券使用记录
 * @property Activity\ActivityFdClient               $activityFd            满减满送活动
 * @property Activity\ActivityFdUsedClient           $activityFdUsed        满减满送使用记录
 * @property Activity\ActivityLotteryClient          $activityLottery       付费抽奖活动
 * @property Activity\ActivityLotteryUsedClient      $activityLotteryUsed   付费抽奖活动使用记录
 * @property Activity\ActivityPickClient             $activityPick          提货码活动
 * @property Activity\ActivityPickCodeClient         $activityPickCode      提货码使用记录
 *
 * @property Advertisement\AdvertisementPushClient          $advertisementPush      广告推送
 * @property Advertisement\AdvertisementRecordClient        $advertisementRecord    广告播放记录
 *
 * @property Auth\AuthManagerRoleClient             $authManagerRole        管理员绑定角色
 * @property Auth\AuthManagerMachineClient          $authManagerMachine     管理员绑定设备
 * @property Auth\AuthManagerClient                 $authManager            管理员
 * @property Auth\AuthManagerLogClient              $authManagerLog         管理员操作日志
 * @property Auth\AuthNodeClient                    $authNode               权限节点
 * @property Auth\AuthRoleClient                    $authRole               权限角色
 * @property Auth\AuthRoleNodeClient                $authRoleNode           权限角色绑定权限节点
 * @property Auth\AuthOrganizationClient            $authOrganization       组织架构
 * @property Auth\AuthOrganizationRoleClient        $authOrganizationRole   组织架构关联权限角色
 *
 * @property Config\ConfigClient                    $config                 系统配置
 * @property Config\ConfigApiClient                 $configApi              API对外用户
 * @property Config\ConfigSizeClient                $configSize             尺寸管理
 * @property Config\ConfigSceneClient               $configScene            场景管理
 * @property Config\ConfigLangClient                $configLang             语言管理
 * @property Config\ConfigPerformanceClient         $configPerformance      性能参数配置
 *
 * @property Earth\EarthClient                      $earth                  全球地区信息与时区
 *
 * @property Export\ExportLogClient                 $exportLog                 导出日志
 *
 * @property Email\EmailConfigClient                $emailConfig            邮箱配置
 * @property Email\EmailTemplateClient              $emailTemplate          邮件模板配置
 * @property Email\EmailTemplateLogClient           $emailTemplateLog       邮件模板通知日志
 *
 * @property Goods\GoodsClient                      $goods                  商品信息
 * @property Goods\GoodsLangClient                  $goodsLang              商品多语言信息
 * @property Goods\GoodsCategoryClient              $goodsCategory          商品分类信息
 * @property Goods\GoodsCategoryLangClient          $goodsCategoryLang      商品分类语言信息
 * @property Goods\GoodsChangeClient                $goodsChange            商品变化事件
 * @property Goods\GoodsCornerClient                $goodsCorner            商品角标信息
 * @property Goods\GoodsHitClient                   $goodsHit               商品点击
 * @property Goods\GoodsMultipleClient              $goodsMultiple          组合商品
 *
 * @property Hotel\HotelClient                      $hotel                  携程酒店
 *
 * @property Machine\MachineChannelClient           $machineChannel         设备货道
 * @property Machine\MachineChannelStockClient      $machineChannelStock    库存报表-分时段,暂时废弃
 * @property Machine\MachineChannelStockReportClient      $machineChannelStockReport    库存报表-实时
 * @property Machine\MachineChannelReplenishmentClient     $machineChannelReplenishment         设备货道补货
 * @property Machine\MachineCheckStockClient        $machineCheckStock      库存盘点详情
 * @property Machine\MachineCheckStockCountClient   $machineCheckStockCount      库存盘点汇总
 * @property Machine\MachineClient                  $machine                设备基础信息
 * @property Machine\MachineConfigClient            $machineConfig          设备配置信息
 * @property Machine\MachineConfigLangClient        $machineConfigLang      设备配置语言信息
 * @property Machine\MachineErrorCodeClient         $machineErrorCode       设备错误码信息
 * @property Machine\MachineErrorCodeSolutionClient $machineErrorCodeSolution    设备错误码解决方案
 * @property Machine\MachineGoodsClient             $machineGoods           设备商品信息
 * @property Machine\MachineGroupClient             $machineGroup           设备分组
 * @property Machine\MachineGroupLangClient         $machineGroupLang       设备分组多语言信息
 * @property Machine\MachineGroupMgClient           $machineGroupMg         设备与分组关联
 * @property Machine\MachineHelpClient              $machineHelp            设备帮助
 * @property Machine\MachineInfoClient              $machineInfo            设备信息
 * @property Machine\MachineLangClient              $machineLang            设备主体多语言数据
 * @property Machine\MachineViewClient              $machineView            设备视图
 * @property Machine\MachineOnlineClient            $machineOnline          设备每天在线时长统计
 * @property Machine\MachineOnlineDetailsClient     $machineOnlineDetails   设备在线时长详情
 * @property Machine\MachineOnOffClient             $machineOnOff           设备营业配置
 * @property Machine\MachineVersionClient           $machineVersion         设备软件版本
 * @property Machine\MachineVersionPlanClient       $machineVersionPlan     设备软件发布计划
 * @property Machine\SimCardInfoClient              $simCardInfo            物联卡基础信息
 * @property Machine\MachineServiceLogClient        $machineServiceLog      设备运行日志
 * @property Mall\MallClient                        $mall                   商场管理
 * @property RemoteActionLog\RemoteActionLogClient  $remoteActionLog        远程操作日志管理
 *
 * @property Card\CardClient                        $card                   商场管理会员卡
 * @property Card\CardActivationClient              $cardActivation         会员卡激活活动
 * @property WeiCheng\WeiChengClient                $weicheng               微程接口管理
 * 
 * @property MicroMall\MicroMallClient              $microMall              微商城
 * @property MicroMall\MicroMallMachineClient       $microMallMachine       微商城绑定设备
 *
 * @property Resource\ResourceClient                $resource  广告素材
 *
 * @property Template\TemplateClient                $template               模板
 * @property Template\TemplatePluginsClient         $templatePlugins        模板组件
 * @property Template\TemplateLayoutClient          $templateLayout         模板布局
 * @property Template\TemplateViewClient            $templateView           模板视图
 * @property Template\TopicPageClient               $topicPage              主题页
 *
 * @property Trip\TripMultipleClient                $tripMultiple           携程套餐
 * @property Trip\TripMultipleGoodsClient           $tripMultipleGoods      携程套餐商品
 * @property Trip\TripMultipleMachineClient         $tripMultipleMachine    携程套餐设备
 * @property Trip\TripMultipleHotelClient           $tripMultipleHotel      携程套餐酒店
 *
 * @property Sale\SaleOrdersClient                  $saleOrders             销售订单
 * @property Sale\SaleOrdersUnclaimedClient         $saleOrdersUnclaimed    销售订单未取商品
 *
 * @property Strategy\StrategyIncomeClient          $strategyIncome         分润策略
 * @property Strategy\StrategyMachineClient         $strategyMachine        策略绑定设备
 * @property Strategy\StrategyManagerClient         $strategyManager        策略绑定账号
 * @property Strategy\StrategyPayeeClient           $strategyPayee          收款策略
 *
 * @property Suggest\SuggestClient                  $suggest                意见与建议
 *
 * @property UpdateLog\UpdateLogClient              $updateLog              更新日志
 *
 * @property Wx\WxOfficialClient                    $wxOfficial             微信公众号
 * @property Wx\WxTemplateClient                    $wxTemplate             微信公众号消息模板
 * @property Wx\WxTemplateLogClient                 $wxTemplateLog          微信公众号消息模板通知日志
 * @property Machine\MachineAppSettingsClient       $machineAppSettings     设备应用配置
 * @property Machine\MachineCalibrationConfigClient $machineCalibrationConfig 设备校准配置
 *
 *
 * @package app\AppFactory\Management
 */
class Application extends ServiceContainer
{
    use ConfigTrait;

    protected $providers = [
        ActionProvider::class,
        ActivityProvider::class,
        AdvertisementProvider::class,
        AuthProvider::class,
        IndexProvider::class,
        CommonProvider::class,
        ConfigProvider::class,
        EarthProvider::class,
        EmailProvider::class,
        ExportProvider::class,
        LoginProvider::class,
        HotelProvider::class,
        MachineProvider::class,
        MicroMallProvider::class,
        GoodsProvider::class,
        ResourceProvider::class,
        SaleOrdersProvider::class,
        StrategyProvider::class,
        SuggestProvider::class,
        TemplateProvider::class,
        TripProvider::class,
        UpdateLogProvider::class,
        WxProvider::class,
        MallProvider::class,
        RemoteActionLogProvider::class,
        CardProvider::class,
        WeiChengProvider::class,
    ];

}