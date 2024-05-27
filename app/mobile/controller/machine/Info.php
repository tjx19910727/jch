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
        try {
            return $this->app->machineInfo->getInfo();
        } catch (\Exception $e) {
            return returnTryCatch($e->getMessage());
        }
    }

    public function getChannel()
    {
        try {
            return $this->app->machineInfo->getChannel();
        } catch (\Exception $e) {
            return returnTryCatch($e->getMessage());
        }
    }

    public function getMachineGoods()
    {
        try {
            return $this->app->machineInfo->getMachineGoods();
        } catch (\Exception $e) {
            return returnTryCatch($e->getMessage());
        }
    }
}