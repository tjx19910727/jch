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