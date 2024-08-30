<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/26
 * Time: 15:36
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineFree;

class MachineFree extends Common
{

    protected $field = "*";
    protected $validatePath = VMachineFree::class  . ".";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineFree->getList($where,$pageNum,$this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineFree->getMfFind($where);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineFree->addMf($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineFree->updateMf($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineFree->delMf($postData['mf_id']);
    }
}