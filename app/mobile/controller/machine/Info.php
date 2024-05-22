<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 17:18
 */

namespace app\mobile\controller\machine;


use app\mobile\controller\Common;
use think\App;

class Info extends Common
{

    public function getMachine()
    {
        return $this->app->machineInfo->getInfo();
    }

    public function getChannel()
    {
        return $this->app->machineInfo->getChannel();
    }
}