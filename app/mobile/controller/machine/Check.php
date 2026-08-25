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
     * 扫描巡检二维码后输入巡检号登录。
     */
    public function login()
    {
        return $this->app->inspectionCheck->login(input());
    }

    /**
     * 获取 H5 巡检清单。
     */
    public function getCheckListItems()
    {
        return $this->app->inspectionCheck->getCheckListItems();
    }

    /**
     * 提交 H5 巡检记录。
     */
    public function submitCheckListRecord()
    {
        return $this->app->inspectionCheck->submitCheckListRecord(input());
    }

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