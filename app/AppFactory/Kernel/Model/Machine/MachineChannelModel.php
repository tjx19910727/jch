<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:26
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\BaseModel;
use think\Model;

class MachineChannelModel extends BaseModel
{
    protected $pk = "mc_id";
    protected $name = "machine_channel";

    public static function joinGoodsList($where,$field = "*", $order = "",$group = "")
    {
        $data = self::alias("mc")
            ->join("goods g","g.g_id = mc.g_id","left")
            ->join("machine m","m.m_id = mc.m_id","left")
            ->where($where)
            ->field($field)
            ->order($order)
            ->group($group)
            ->select();
        return $data;
    }

    /**
     * 删除后通知下发设备终端更新
     * @param Model $model
     */
    protected static function onAfterDelete(Model $model)
    {
        $where = $model->getWhere();
        if (!$where) $where['mc_id'] = $model['mc_id'];
        if ($where) {
            $mc = self::getList($where, 0, 'mg_id,machine_id');
            if ($mc) {
                $config = [
                    "machine_id" => $mc[0]['machine_id'],
                    "key" => env("api.md5Key"),
                ];
                $app = AppFactory::machine($config);
                foreach ($mc as $k => $v) {
                    @$app->sendMq->triggerUpdateMc($v['mc_id']);
                }
            }
        }
    }
}