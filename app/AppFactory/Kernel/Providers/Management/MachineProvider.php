<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/18
 * Time: 9:41
 */

namespace app\AppFactory\Kernel\Providers\Management;


use app\AppFactory\Kernel\Container;
use app\AppFactory\Kernel\ServiceProviderInterface;
use app\AppFactory\Management\Machine\MachineChannelClient;
use app\AppFactory\Management\Machine\MachineAppSettingsClient;
use app\AppFactory\Management\Machine\MachineCalibrationConfigClient;
use app\AppFactory\Management\Machine\MachineChannelReplenishmentClient;
use app\AppFactory\Management\Machine\MachineChannelStockClient;
use app\AppFactory\Management\Machine\MachineChannelStockReportClient;
use app\AppFactory\Management\Machine\MachineCheckStockClient;
use app\AppFactory\Management\Machine\MachineCheckStockCountClient;
use app\AppFactory\Management\Machine\MachineCheckListClient;
use app\AppFactory\Management\Machine\MachineClient;
use app\AppFactory\Management\Machine\MachineConfigClient;
use app\AppFactory\Management\Machine\MachineConfigLangClient;
use app\AppFactory\Management\Machine\MachineErrorCodeClient;
use app\AppFactory\Management\Machine\MachineErrorCodeSolutionClient;
use app\AppFactory\Management\Machine\MachineGoodsClient;
use app\AppFactory\Management\Machine\MachineGroupClient;
use app\AppFactory\Management\Machine\MachineGroupLangClient;
use app\AppFactory\Management\Machine\MachineGroupMgClient;
use app\AppFactory\Management\Machine\MachineHelpClient;
use app\AppFactory\Management\Machine\MachineInfoClient;
use app\AppFactory\Management\Machine\MachineLangClient;
use app\AppFactory\Management\Machine\MachineMaintenanceClient;
use app\AppFactory\Management\Machine\MachineOnlineClient;
use app\AppFactory\Management\Machine\MachineOnlineDetailsClient;
use app\AppFactory\Management\Machine\MachineOnOffClient;
use app\AppFactory\Management\Machine\MachinePreReplenishmentClient;
use app\AppFactory\Management\Machine\MachineVersionClient;
use app\AppFactory\Management\Machine\MachineVersionPlanClient;
use app\AppFactory\Management\Machine\OtaVersionClient;
use app\AppFactory\Management\Machine\OtaVersionPlanClient;
use app\AppFactory\Management\Machine\MachineViewClient;
use app\AppFactory\Management\Machine\SimCardInfoClient;
use app\AppFactory\Management\Machine\MachineServiceLogClient;

class MachineProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        // TODO: Implement register() method.
        $app['machineChannelReplenishment'] = function ($app) {
            return new MachineChannelReplenishmentClient($app);
        };
        $app['machinePreReplenishment'] = function ($app) {
            return new MachinePreReplenishmentClient($app);
        };
        $app['machineChannel'] = function ($app) {
            return new MachineChannelClient($app);
        };
        $app['machineChannelStock'] = function ($app) {
            return new MachineChannelStockClient($app);
        };
        $app['machineChannelStockReport'] = function ($app) {
            return new MachineChannelStockReportClient($app);
        };
        $app['machineCheckStock'] = function ($app) {
            return new MachineCheckStockClient($app);
        };
        $app['machineCheckStockCount'] = function ($app) {
            return new MachineCheckStockCountClient($app);
        };
        $app['machineCheckList'] = function ($app) {
            return new MachineCheckListClient($app);
        };
        $app['machine'] = function ($app) {
            return new MachineClient($app);
        };
        $app['machineConfig'] = function ($app) {
            return new MachineConfigClient($app);
        };
        $app['machineAppSettings'] = function ($app) {
            return new MachineAppSettingsClient($app);
        };
        $app['machineCalibrationConfig'] = function ($app) {
            return new MachineCalibrationConfigClient($app);
        };
        $app['machineConfigLang'] = function ($app) {
            return new MachineConfigLangClient($app);
        };
        $app['machineErrorCode'] = function ($app) {
            return new MachineErrorCodeClient($app);
        };
        $app['machineErrorCodeSolution'] = function ($app) {
            return new MachineErrorCodeSolutionClient($app);
        };
        $app['machineGoods'] = function ($app) {
            return new MachineGoodsClient($app);
        };
        $app['machineGroup'] = function ($app) {
            return new MachineGroupClient($app);
        };
        $app['machineGroupMg'] = function ($app) {
            return new MachineGroupMgClient($app);
        };
        $app['machineGroupLang'] = function ($app) {
            return new MachineGroupLangClient($app);
        };
        $app['machineHelp'] = function ($app) {
            return new MachineHelpClient($app);
        };
        $app['machineInfo'] = function ($app) {
            return new MachineInfoClient($app);
        };
        $app['machineLang'] = function ($app) {
            return new MachineLangClient($app);
        };
        $app['machineMaintenance'] = function ($app) {
            return new MachineMaintenanceClient($app);
        };
        $app['machineView'] = function ($app) {
            return new MachineViewClient($app);
        };
        $app['machineOnline'] = function ($app) {
            return new MachineOnlineClient($app);
        };
        $app['machineOnlineDetails'] = function ($app) {
            return new MachineOnlineDetailsClient($app);
        };
        $app['machineOnOff'] = function ($app) {
            return new MachineOnOffClient($app);
        };
        $app['machineVersion'] = function ($app) {
            return new MachineVersionClient($app);
        };
        $app['machineVersionPlan'] = function ($app) {
            return new MachineVersionPlanClient($app);
        };
        $app['otaVersion'] = function ($app) {
            return new OtaVersionClient($app);
        };
        $app['otaVersionPlan'] = function ($app) {
            return new OtaVersionPlanClient($app);
        };
        $app['machineServiceLog'] = function ($app) {
            return new MachineServiceLogClient($app);
        };
        $app['simCardInfo'] = function ($app) {
            return new SimCardInfoClient($app);
        };

    }
}