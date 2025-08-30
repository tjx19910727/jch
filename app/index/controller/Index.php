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

        return View::fetch();
    }
}
