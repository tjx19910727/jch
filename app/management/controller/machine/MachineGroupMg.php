<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/28
 * Time: 15:18
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineGroupMg;

class MachineGroupMg extends Common
{

    protected $field = "*";
    protected $validatePath = VMachineGroupMg::class;

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineGroupMg->getList($where,$pageNum,$this->field,'create_time desc');
    }

    public function mgBindMachine()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.bind');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineGroupMg->mgBindMachine($postData);
    }

    public function machineBindMg()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.bind');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineGroupMg->machineBindMg($postData);
    }

    public function exportM()
    {
        $mg_id = input('mg_id');
        if (strpos($mg_id,",") !== false) $where[] = ['mg_id',"in",$mg_id];
        else $where['mg_id'] = $mg_id;
        return $this->app->machineGroupMg->exportMachine($where);
    }
}