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
    public function testSynchronizationGoods()
    {
        $redis = new \Redis();
        $redis->connect("127.0.0.1");
        $redis->lPush("updateGoods",29);
        dump($redis->lRange("updateGoods",0,-1));
        $redis->close();
    }

    public function testTimeTask()
    {
        $app = AppFactory::timeTask();
        $result = $app->goods->synchronizationGoods();
    }
}