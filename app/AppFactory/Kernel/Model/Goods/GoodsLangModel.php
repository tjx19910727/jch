<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/17
 * Time: 19:59
 */

namespace app\AppFactory\Kernel\Model\Goods;


use app\AppFactory\Kernel\Model\BaseModel;
use think\Model;

class GoodsLangModel extends BaseModel
{
    protected $pk = "gl_id";
    protected $name = "goods_lang";

    protected $schema = [
        "gl_id" => "int",
        "g_id" => "int",
        "g_name" => "string",
        "gc_id" => "int",
        "gc_name" => "string",
        "pic" => "string",
        "banner" => "string",
        "details_pic" => "string",
        "manufacturer" => "string",
        "desc" => "string",
        "performance" => "string",
        "lang" => "string",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];


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

    /**
     * 添加后自动将商品ID放入队列，由守护进程触发通知终端更新商品
     * @param Model $model
     */
    protected static function onAfterInsert(Model $model)
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