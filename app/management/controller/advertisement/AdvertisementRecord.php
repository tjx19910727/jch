<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/10
 * Time: 11:11
 */

namespace app\management\controller\advertisement;


use app\management\controller\Common;

class AdvertisementRecord extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\V.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["adv_title" => "like","machine_id" => "like","res_title" => "like"]);
        $machineIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']],'machine_id');
        if ($machineIds) $where[] = ['machine_id','in',$machineIds];
        return $this->app->advertisementRecord->getList($where,$pageNum,$this->field,'play_time desc');
    }

}