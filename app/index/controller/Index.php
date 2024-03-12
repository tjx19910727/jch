<?php
namespace app\index\controller;

use app\AppFactory\RabbitMq\MqProducer;
use app\BaseController;
use think\facade\Config;
use think\facade\View;

class Index extends BaseController
{
    public function index()
    {

//        return View::fetch();
    }


    public function hello($name = 'ThinkPHP6')
    {
        return 'hello,' . $name;
    }

    public function send()
    {
        $data = [
            'time'=>time(),
            'title'=> 'fanout->'.rand(1,100),
        ];
        dump($data);
        $result = MqProducer::topic($data,"test0001"); //指向topic主题
        dump($result);

    }
}
