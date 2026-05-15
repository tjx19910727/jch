<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/5/15
 * Time: 10:44
 */

namespace app\management\controller\machine;

use app\management\controller\Common;
use app\management\validate\Machine\VMachineMaintenance;

class MachineMaintenance extends Common
{
    protected $field = 'id,parent_id,item_name,item_level,cycle_days,description,sort_order,is_active,created_at,updated_at';

    public function getList()
    {
        $postData = input();
        $pageNum = intval($postData['pageNum'] ?? 0);
        $where = $this->getWhere($postData, false, [
            'item_name' => 'like',
        ]);
        return $this->app->machineMaintenance->getList($where, $pageNum, $this->field, 'sort_order asc,id asc');
    }

    public function getTree()
    {
        $active = input('is_active', 1);
        if ($active === '' || $active === null) {
            $active = -1;
        }
        return $this->app->machineMaintenance->getTree(intval($active));
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineMaintenance->getFind($where, $this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, VMachineMaintenance::class . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineMaintenance->addItem($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, VMachineMaintenance::class . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineMaintenance->updateItem($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, VMachineMaintenance::class . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineMaintenance->delItem($postData);
    }
}
