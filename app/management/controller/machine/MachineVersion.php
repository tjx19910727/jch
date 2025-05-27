<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/24
 * Time: 9:52
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineVersion;

class MachineVersion extends Common
{

    protected $field = "*";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["version_no" => "like"]);
        return $this->app->machineVersion->getList($where,$pageNum,$this->field,'mv_id desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineVersion->getFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, VMachineVersion::class . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineVersion->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, VMachineVersion::class . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineVersion->update($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, VMachineVersion::class . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineVersion->del($postData);
    }
}