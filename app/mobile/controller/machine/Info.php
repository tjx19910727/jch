<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 17:18
 */

namespace app\mobile\controller\machine;


use app\mobile\controller\Common;
use app\mobile\validate\Machine\VMachineCheck;
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

    /**
     * 统一提交货道库存和备用商品库存盘点
     */
    public function newStock()
    {
        $postData = input();
        try {
            $this->validate($postData, VMachineCheck::class . '.newStock');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineCheck->newStock($postData);
    }
}
