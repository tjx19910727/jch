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

    /**
     * 关联商品列表
     * @param $where
     * @param string $field
     * @param string $order
     * @param string $group
     * @return mixed
     */
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
     * 关联自由组合商品列表
     * @param $where
     * @param string $field
     * @param string $order
     * @return MachineChannelModel[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function joinMfgList($where,$field = "*",$order = "")
    {
        $data = self::alias("mc")
            ->join("machine_free_goods mfg","mfg.g_id = mc.g_id","left")
            ->join("goods g","g.g_id = mc.g_id",'left')
            ->where($where)
            ->field($field)
            ->order($order)
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
                    "key" =>
                        cache($mc[0]['machine_id'] . ".signKey") ??
                        (MachineModel::getFieldValue(['machine_id' => $mc[0]['machine_id']],'signKey') ?? env("api.md5Key")),
                ];
                $app = AppFactory::machine($config);
                foreach ($mc as $k => $v) {
                    @$app->sendMq->sendMq("updateMc",['mc_id' => $v['mc_id']]);
                }
            }
        }
    }
}