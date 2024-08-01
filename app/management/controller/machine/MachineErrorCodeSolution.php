<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/30
 * Time: 17:01
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineErrorCodeSolution;

class MachineErrorCodeSolution extends Common
{

    protected $field = "s_id,error_code,title,content,creator,create_time,update_id,update_time";
    protected $validatePath = VMachineErrorCodeSolution::class . ".";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineErrorCodeSolution->getList($where,$pageNum,$this->field,'create_time desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineErrorCodeSolution->getFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineErrorCodeSolution->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineErrorCodeSolution->update($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineErrorCodeSolution->del($postData);
    }
}