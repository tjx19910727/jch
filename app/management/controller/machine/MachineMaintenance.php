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

    /**
     * 管理端：查询维护记录（支持 machine_id / records_code / maintainer_id）
     * 返回结构与设备端 `/machine/receive/getMaintenanceRecords` 保持一致（按 records_code 分组）
     */
    public function getRecords()
    {
        $postData = input();
        $where = [];
        if (!empty($postData['machine_id'])) {
            $where['machine_id'] = $postData['machine_id'];
        } elseif (!empty($postData['device_id'])) {
            // 兼容旧参数
            $where['machine_id'] = $postData['device_id'];
        }
        if (!empty($postData['records_code'])) $where['records_code'] = $postData['records_code'];
        if (isset($postData['maintainer_id'])) $where['maintainer_id'] = $postData['maintainer_id'];
        return $this->app->machineMaintenance->getRecords($where);
    }
}
