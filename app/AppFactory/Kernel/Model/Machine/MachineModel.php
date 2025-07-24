<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:25
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Activity\ActivityMachineModel;
use app\AppFactory\Kernel\Model\Advertisement\AdvertisementPushModel;
use app\AppFactory\Kernel\Model\Auth\AuthManagerMachineModel;
use app\AppFactory\Kernel\Model\BaseModel;
use app\AppFactory\Kernel\Model\Strategy\StrategyMachineModel;
use think\Model;

class MachineModel extends BaseModel
{
    protected $pk = "m_id";
    protected $name = "machine";

    /**
     * 查询设备信息后检查有没有以下三张表数据，没有则新增
     * @param Model $machine
     * @throws \think\db\exception\DbException
     */
    public static function onAfterRead(Model $machine)
    {
        $where['m_id'] = $machine['m_id'];
        $mc = MachineConfigModel::getCount($where);
        if (!$mc)
            MachineConfigModel::create(['m_id' => $machine['m_id'],"machine_id" => $machine['machine_id']]);
        $mi = MachineInfoModel::getCount($where);
        if (!$mi)
            MachineInfoModel::create(['m_id' => $machine['m_id'],"machine_id" => $machine['machine_id']]);
        $mof = MachineOnOffModel::getCount($where);
        if (!$mof)
            MachineOnOffModel::create(['m_id' => $machine['m_id'],"machine_id" => $machine['machine_id'],"machine_name" => $machine['machine_name']]);
    }

    /**
     * 添加设备后，自动生成设备配置信息、设备信息、设备营业配置信息
     * @param Model $machine
     */
    protected static function onAfterInsert(Model $machine)
    {
        MachineConfigModel::create(['m_id' => $machine['m_id'],"machine_id" => $machine['machine_id']]);
        MachineInfoModel::create(['m_id' => $machine['m_id'],"machine_id" => $machine['machine_id']]);
        MachineOnOffModel::create(['m_id' => $machine['m_id'],"machine_id" => $machine['machine_id'],"machine_name" => $machine['machine_name']]);

        $config = [
            "machine_id" => $machine['machine_id'],
            "key" => env("api.md5Key"),
        ];
        $app = AppFactory::machine($config);
        @$app->sendMq->sendMq("updateMachine");
    }

    /**
     * 删除设备前执行的操作
     * @param Model $model
     * @return mixed|void
     */
    protected static function onBeforeDelete(Model $model)
    {
        $where['m_id'] = $model['m_id'];

        // 删除设备副表
        MachineChannelModel::whereDel($where);
        MachineChannelReplenishmentModel::whereDel($where);
        MachineChannelStockModel::whereDel($where);
        MachineCheckStockModel::whereDel($where);
        MachineConfigModel::whereDel($where);
        MachineConfigLangModel::whereDel($where);
        MachineErrorCodeModel::whereDel($where);
        MachineGoodsModel::whereDel($where);
        MachineGroupMgModel::whereDel($where);
        MachineHelpModel::whereDel($where);
        MachineInfoModel::whereDel($where);
        MachineLangModel::whereDel($where);
        MachineMqRecordModel::whereDel($where);
        MachineOnOffModel::whereDel($where);
        MachineOnlineModel::whereDel($where);
        MachineOnlineDetailsModel::whereDel($where);
        MachineVersionPlanModel::whereDel($where);
        MachineViewModel::whereDel($where);

        // 删除广告推送
        AdvertisementPushModel::whereDel($where);

        // 删除活动绑定
        ActivityMachineModel::whereDel($where);

        // 删除管理员绑定
        AuthManagerMachineModel::whereDel($where);

        // 删除策略绑定
        StrategyMachineModel::whereDel($where);

    }
    /**
     * 修改后下发通知设备更新
     * @param Model $model
     */
//    protected static function onAfterUpdate($model)
//    {
//        $where = $model->getWhere();
//        if (!$where) $where['m_id'] = $model['m_id'];
//        if ($where) {
//            $machine_id = self::getFieldValue($where, 'machine_id');
//            if ($machine_id) {
//                $config = [
//                    "machine_id" => $machine_id,
//                    "key" => env("api.md5Key"),
//                ];
//                $app = AppFactory::machine($config);
//                @$app->sendMq->triggerUpdateMachine();
//            }
//        }
//    }

    /**
     * 删除后下发通知设备更新
     * @param Model $model
     */
//    protected static function onAfterDelete(Model $model)
//    {
//        $where = $model->getWhere();
//        if (!$where) $where['m_id'] = $model['m_id'];
//        if ($where) {
//            $machine_id = self::getFieldValue($where, 'machine_id');
//            if ($machine_id) {
//                $config = [
//                    "machine_id" => $machine_id,
//                    "key" => env("api.md5Key"),
//                ];
//                $app = AppFactory::machine($config);
//                @$app->sendMq->triggerUpdateMachine();
//            }
//        }
//    }
}