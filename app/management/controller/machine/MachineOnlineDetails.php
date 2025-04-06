<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 9:00
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class MachineOnlineDetails extends Common
{

    protected $field = "*";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineOnlineDetails->getList($where,$pageNum,$this->field,'mod_id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineOnlineDetails->getFind($where,$this->field);
    }

}