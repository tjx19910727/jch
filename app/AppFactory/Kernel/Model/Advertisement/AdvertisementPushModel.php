<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/6
 * Time: 16:57
 */

namespace app\AppFactory\Kernel\Model\Advertisement;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\BaseModel;
use think\Model;

class AdvertisementPushModel extends BaseModel
{
    protected $pk = "adv_id";
    protected $name = "advertisement_push";

//    protected $schema = [
//        "adv_id" => "int",
//        "adv_title" => "int",
//        "res_id" => "int",
//        "res_title" => "string",
//        "file_path" => "string",
//        "duration_time" => "int",
//        "total_times" => "int",
//        "play_times" => "int",
//        "remain_times" => "int",
//        "m_id" => "int",
//        "machine_name" => "string",
//        "machine_id" => "string",
//        "start_date" => "int",
//        "end_date" => "int",
//        "start_time" => "int",
//        "end_time" => "int",
//        "position" => "int",
//        "screen" => "int",
//        "screen_full" => "int",
//        "status" => "int",
//        "creator" => "int",
//        "create_time" => "int",
//        "update_id" => "int",
//        "update_time" => "int",
//    ];


    /**
     * 新增后下发通知设备更新
     * @param Model $model
     */
    protected static function onAfterInsert(Model $model)
    {
        $config = [
            "machine_id" => $model['machine_id'],
            "key" => env("api.md5Key"),
        ];
        $app = AppFactory::machine($config);
        @$app->sendMq->triggerUpdateAD();
    }

    /**
     * 修改后下发通知设备更新
     * @param Model $model
     */
//    protected static function onAfterUpdate($model)
//    {
//        $where = $model->getWhere();
//        if (!$where) $where['adv_id'] = $model['adv_id'];
//        if ($where) {
//            $machine_id = self::getFieldValue($where, 'machine_id');
//            if ($machine_id) {
//                $config = [
//                    "machine_id" => $machine_id,
//                    "key" => env("api.md5Key"),
//                ];
//                $app = AppFactory::machine($config);
//                @$app->sendMq->triggerUpdateAD();
//            }
//        }
//    }

    /**
     * 删除后下发通知设备更新
     * @param Model $model
     */
    protected static function onAfterDelete(Model $model)
    {
        $where = $model->getWhere();
        if (!$where) $where['adv_id'] = $model['adv_id'];
        if ($where) {
            $machine_id = self::getFieldValue($where, 'machine_id');
            if ($machine_id) {
                $config = [
                    "machine_id" => $machine_id,
                    "key" => env("api.md5Key"),
                ];
                $app = AppFactory::machine($config);
                @$app->sendMq->triggerUpdateAD();
            }
        }
    }
}