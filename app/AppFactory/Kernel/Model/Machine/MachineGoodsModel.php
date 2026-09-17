<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:28
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\BaseModel;
use think\Model;
use think\cache\driver\Redis;

class MachineGoodsModel extends BaseModel
{
    protected $pk = "mg_id";
    protected $name = "machine_goods";

    /**
     * 设备商品库存查询字段（可用库存/不可用库存/预定量）。
     * 注意：machine_goods 表上虽存在同名列，但属历史遗留且不再维护（恒为 0），
     * 真实库存以 machine_channel 货道库存汇总为准；列表与导出必须共用本常量，避免口径不一致。
     * 使用前提：查询必须 alias('a')。
     */
    const STOCK_FIELDS = '(SELECT sum(mc.stock) FROM machine_channel mc where mc.m_id = a.m_id AND mc.status = 1 AND mc.mg_id = a.mg_id) available_stock,'
        . '(SELECT sum(mc.stock) FROM machine_channel mc where mc.m_id = a.m_id AND mc.status > 1 AND mc.mg_id = a.mg_id) disabled_stock,'
        . '(SELECT sum(mc.frozen_stock) FROM machine_channel mc where mc.m_id = a.m_id AND mc.mg_id = a.mg_id) reserve_stock';

    /**
     * 管理端设备商品列表只展示已上架或仍有任一库存的商品。
     *
     * 已有货道的商品需要保留，由列表现有逻辑将可能滞后的 is_shelf 修正为已上架；
     * 三类货道库存必须沿用 STOCK_FIELDS 的实时汇总口径，不能读取历史遗留列。
     *
     * @return string
     */
    public static function managementListVisibleWhereRaw()
    {
        return '('
            . 'COALESCE(a.is_shelf, 2) <> 2'
            . ' OR EXISTS(SELECT 1 FROM machine_channel mc_shelf'
            . ' WHERE mc_shelf.m_id = a.m_id AND mc_shelf.g_id = a.g_id)'
            . ' OR COALESCE((SELECT SUM(mc_available.stock) FROM machine_channel mc_available'
            . ' WHERE mc_available.m_id = a.m_id AND mc_available.status = 1 AND mc_available.mg_id = a.mg_id), 0) <> 0'
            . ' OR COALESCE((SELECT SUM(mc_disabled.stock) FROM machine_channel mc_disabled'
            . ' WHERE mc_disabled.m_id = a.m_id AND mc_disabled.status > 1 AND mc_disabled.mg_id = a.mg_id), 0) <> 0'
            . ' OR COALESCE((SELECT SUM(mc_reserved.frozen_stock) FROM machine_channel mc_reserved'
            . ' WHERE mc_reserved.m_id = a.m_id AND mc_reserved.mg_id = a.mg_id), 0) <> 0'
            . ' OR COALESCE(a.standby_stock, 0) <> 0'
            . ')';
    }

    /**
     * 新增后下发通知设备更新
     * @param Model $model
     */
    public static function afterInsert($model)
    {
        $config = [
            "machine_id" => $model['machine_id'],
            "key" =>
                cache($model['machine_id'] . ".signKey") ??
                (MachineModel::getFieldValue(['machine_id' => $model['machine_id']],'signKey') ?? env("api.md5Key")),
        ];
        $app = AppFactory::machine($config);
        @$app->sendMq->sendMq("updateMg",['mg_id' => $model['mg_id']]);
    }

    /**
     * 修改设备商品库，放入Redis，由系统后台守护进程触发同步到设备货架，下发触发设备更新
     * @param int $mg_id
     */
    public static function AfterUpdate($mg_id)
    {
        $redis = new Redis();
        $config = config("redis");
        $redis->connect($config['host'], $config['port'],$config['timeout'],$config['reserved'],$config['retry_interval']);
        if (isset($config['password']) && $config['password']) $redis->auth($config['password']);
        $redis->lPush("updateMg",$mg_id);
        $redis->expire("updateMg",300);
        $redisData = $redis->lRange("updateMg", 0, -1);
        actionLog($mg_id,'修改的设备商品ID');
        actionLog($redisData,'放入Redis数据');
        $redis->close();
    }

    /**
     * 删除后下发通知设备更新
     * @param Model $model
     */
    protected static function onAfterDelete(Model $model)
    {
        $where = $model->getWhere();
        if (!$where) $where['mg_id'] = $model['mg_id'];
        if ($where) {
            $mg = self::getList($where, 0, 'mg_id,machine_id');
            if ($mg) {
                $config = [
                    "machine_id" => $mg[0]['machine_id'],
                    "key" =>
                        cache($mg[0]['machine_id'] . ".signKey") ??
                        (MachineModel::getFieldValue(['machine_id' => $mg[0]['machine_id']],'signKey') ?? env("api.md5Key")),
                ];
                $app = AppFactory::machine($config);
                foreach ($mg as $k => $v) {
                    $app->sendMq->sendMq("updateMg",['mg_id' => $v['mg_id']]);
                }
            }
        }
    }

    public static function getMGoodsListJoinGoods($where,$pageNum = 0, $field = "*",$order = "")
    {
        $data = self::alias("mg")
            ->join("goods g","mg.g_id = g.g_id","left")
            ->where($where)
            ->field($field)
            ->order($order);
        if ($pageNum) {
            $data = $data->paginate($pageNum,false,['query' => request()->param()]);
        } else {
            $data = $data->select();
        }
        return $data;
    }

}
