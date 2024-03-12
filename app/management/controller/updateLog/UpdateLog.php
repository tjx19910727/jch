<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/1
 * Time: 17:16
 */

namespace app\management\controller\updateLog;


use app\management\controller\Common;

class UpdateLog extends Common
{

    protected $field = "*";
    protected $validatePath = 'app\management\validate\VUpdateLog.';

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->updateLog->getList($where,$pageNum,$this->field,'create_time desc');
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->updateLog->getFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->updateLog->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->updateLog->update($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->updateLog->del($postData);
    }
}