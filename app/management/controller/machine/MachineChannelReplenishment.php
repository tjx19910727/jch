<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/12
 * Time: 20:02
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class MachineChannelReplenishment extends Common
{
    public function getList()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['machine_id' => "like","machine_name" => "like","sku" => "like","channel_code" => "like"]);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->machineChannelReplenishment->getList($where,$pageNum,'*','id desc');
    }

    // 导出补货报表
    public function export()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['machine_id' => "like","machine_name" => "like","sku" => "like","channel_code" => "like"]);
        return $this->app->machineChannelReplenishment->export($where);
    }

}