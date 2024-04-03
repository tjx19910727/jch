<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 16:23
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class MachineHelp extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\VMachineHelp.';

    public function getList()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'getList');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["mh_id" => "in"]);
        return $this->app->machineHelp->getList($where,$pageNum,$this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineHelp->getFind($where);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineHelp->addMore($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'updateMore');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineHelp->updateMore($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $where[] = ['mh_id','in',$postData['mh_id']];
        return $this->app->machineHelp->del($where);
    }
}