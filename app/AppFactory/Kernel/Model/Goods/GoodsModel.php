<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:59
 */

namespace app\AppFactory\Kernel\Model\Goods;


use app\AppFactory\Kernel\Model\BaseModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineGoodsModel;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Model;

class GoodsModel extends BaseModel
{
    protected $name = "goods";
    protected $pk = "g_id";

//    protected $schema = [
//        "g_id" => "int",
//        "g_name" => "string",
//        "gc_id" => "int",
//        "gc_name" => "string",
//        "model" => "string",
//        "bar_code" => "string",
//        "sku" => "string",
//        "sku2" => "string",
//        "pic" => "string",
//        "cost_price" => "float",
//        "market_price" => "float",
//        "retail_price" => "float",
//        "manufacturer" => "string",
//        "service_phone" => "string",
//        "desc" => "string",
//        "performance" => "string",
//        "sell_channel" => "int",
//        "expire_notice" => "int",
//        "length" => "int",
//        "width" => "int",
//        "height" => "int",
//        "group_quantity" => "int",
//        "status" => "int",
//        "ao_id" => "int",
//        "creator" => "int",
//        "create_time" => "int",
//        "update_id" => "int",
//        "update_time" => "int",
//    ];

    /**
     * 关联设备商品列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param int $m_id
     * @return GoodsModel|GoodsModel[]|array|string|\think\Collection|\think\Paginator
     */
    public static function joinMachineGoodsList($where,$pageNum = 0,$field = "*", $order = "",$m_id = 0)
    {
        try {
            $condition = "mg.g_id = g.g_id";
            if ($m_id) $condition .= " AND mg.m_id = $m_id";
            $data = self::alias("g")
                ->join("machine_goods mg", $condition, "left")
                ->where($where)
                ->field($field)
                ->order($order);
            if ($pageNum) {
                $data = $data->paginate($pageNum, false, ["query" => request()->param()]);
                return $data;
            }
            return $data->select();
        } catch (DataNotFoundException $e) {
            actionException($e,1);
            return $e->getMessage();
        } catch (ModelNotFoundException $e) {
            actionException($e,1);
            return $e->getMessage();
        } catch (DbException $e) {
            actionException($e,1);
            return $e->getMessage();
        }
    }

    /**
     * 关联设备商品Find
     * @param $where
     * @param string $field
     * @param string $order
     * @return GoodsModel|array|mixed|null|string|\think\Model
     */
    public static function joinMachineGoodsFind($where,$field = "*", $order = "")
    {
        try {
            $data = self::alias("g")
                ->join("machine_goods mg", "mg.g_id = g.g_id", "left")
                ->where($where)
                ->field($field)
                ->order($order)
                ->find();
            return $data;
        } catch (DataNotFoundException $e) {
            actionException($e,1);
            return $e->getMessage();
        } catch (ModelNotFoundException $e) {
            actionException($e,1);
            return $e->getMessage();
        } catch (DbException $e) {
            actionException($e,1);
            return $e->getMessage();
        }
    }

    /**
     * 修改后自动将商品ID放入队列，由守护进程去后台更新设备货道与设备商品库，两个位置在修改后会自动触发终端更新
     * @param Model $model
     */
    protected static function onAfterUpdate(Model $model)
    {
        $redis = new \Redis();
        $config = config("redis");
        $redis->connect($config['host'], $config['port'],$config['timeout'],$config['reserved'],$config['retry_interval']);
        if (isset($config['password']) && $config['password']) $redis->auth($config['password']);
        $redis->lPush("updateGoods",$model['g_id']);
        $redis->expire("updateGoods",300);
        $redisData = $redis->lRange("updateGoods", 0, -1);
        actionLog($model['g_id'],'修改的商品ID');
        actionLog($redisData,'放入Redis数据');
        $redis->close();
    }

}