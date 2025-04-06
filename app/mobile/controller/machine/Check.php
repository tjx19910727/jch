<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 8:39
 */

namespace app\mobile\controller\machine;


use app\mobile\controller\Common;
use app\mobile\validate\Machine\VMachineCheck;
use think\App;

class Check extends Common
{

    /**
     * 盘点库存
     * @return array|string
     */
    public function stock()
    {
        $postData = input();
        try { $this->validate($postData, VMachineCheck::class . '.stock');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->machineCheck->channelStock($postData);
    }
}