<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/23
 * Time: 16:20
 */

namespace app\http\controller;


use app\AppFactory\AppFactory;
use app\BaseController;

class Test extends BaseController
{
    public function testMethod()
    {
        dump(method_exists($this,"testMicroQuery"));
    }

    public function testMicroQuery()
    {
        $redisExpire = env("Payment.microPayOverTime");
        $data = '{"order_id":8164,"time":1732863980,"pay_type":"wx","query":1}';
        $data = json2arr($data);
        dump($data);
//        $redis = new \Redis();
//        $config = config("redis");
//        $redis->connect($config['host'], $config['port'],$config['timeout'],$config['reserved'],$config['retry_interval']);
//        if (isset($config['password']) && $config['password']) $redis->auth($config['password']);
//        $redis->lPush("microPay", json_encode($data, 256 + 64));
//        $redis->expire("microPay", $redisExpire + 60);
//        $redis->close();
        $app = AppFactory::timeTask();
        $result = $app->wx->queryMicroPay($data['order_id']);
        dump($result);
    }
    public function testGoods()
    {
        $g_id = input("g_id");
        $redis = new \Redis();
        $redis->connect("127.0.0.1");
        $redis->lPush("updateGoods",$g_id);
        dump($redis->lRange("updateGoods",0,-1));
        $redis->close();
    }

    public function testMg()
    {
        $mg_id = input("mg_id");
        $redis = new \Redis();
        $redis->connect("127.0.0.1");
        $redis->lPush("updateMg",$mg_id);
        dump($redis->lRange("updateMg",0,-1));
        $redis->close();
    }

    public function testUpdateGoods()
    {
        $app = AppFactory::timeTask();
        $result = $app->goods->updateGoodsSynchronization();
        dump($result);
    }

    public function testUpdateMg()
    {
        $app = AppFactory::timeTask();
        $result = $app->goods->updateMgSynchronization();
        dump($result);
    }
}