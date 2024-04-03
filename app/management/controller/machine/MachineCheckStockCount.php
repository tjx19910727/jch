<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/13
 * Time: 20:01
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class MachineCheckStockCount extends Common
{
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['machine_id' => "like","creator_nickname" => "like"]);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->machineCheckStockCount->getList($where,$pageNum,'*','create_time desc');
    }

//    public function exportCheckStockCount()
//    {
//        $postData = input();
//        $where = $this->getWhere($postData,false,['machine_id' => "like","creator_nickname" => "like"]);
//        return $this->app->machineCheckStockCount->export($where);
//    }
}