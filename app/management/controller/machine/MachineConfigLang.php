<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/5/15
 * Time: 15:17
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineConfigLang;

class MachineConfigLang extends Common
{
    
    protected $field = "*";
    protected $validatePath = VMachineConfigLang::class . ".";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineConfigLang->getList($where, $pageNum, $this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineConfigLang->getFind($where, $this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineConfigLang->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineConfigLang->update($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineConfigLang->del($postData);
    }
}