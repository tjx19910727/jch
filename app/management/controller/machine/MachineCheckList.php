<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/5/15
 * Time: 14:22
 */

namespace app\management\controller\machine;

use app\management\controller\Common;

class MachineCheckList extends Common
{
    protected $field = 'id,parent_id,item_name,item_level,description,sort_order,is_active,created_at,updated_at';

    public function getList()
    {
        $postData = input();
        $pageNum = intval($postData['pageNum'] ?? 0);
        $where = $this->getWhere($postData, false, [
            'item_name' => 'like',
        ]);
        return $this->app->machineCheckList->getList($where, $pageNum, $this->field, 'sort_order asc,id asc');
    }

    public function getTree()
    {
        $active = input('is_active', 1);
        if ($active === '' || $active === null) {
            $active = -1;
        }
        return $this->app->machineCheckList->getTree(intval($active));
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineCheckList->getFind($where, $this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, 'app\\management\\validate\\Machine\\VMachineCheckList.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineCheckList->addItem($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, 'app\\management\\validate\\Machine\\VMachineCheckList.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineCheckList->updateItem($postData);
    }

    /**
     * 启用/禁用检查项
     */
    public function setActive()
    {
        $postData = input();
        try {
            $this->validate($postData, 'app\\management\\validate\\Machine\\VMachineCheckList.setActive');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineCheckList->setActive($postData);
    }

    /**
     * 管理端：查询检查记录（支持 machine_id / records_code / maintainer_id）
     */
    public function getRecords()
    {
        $postData = input();
        $where = [];
        if (!empty($postData['machine_id'])) {
            $where['machine_id'] = $postData['machine_id'];
        }
        if (!empty($postData['records_code'])) {
            $where['records_code'] = $postData['records_code'];
        }
        if (isset($postData['maintainer_id'])) {
            $where['maintainer_id'] = $postData['maintainer_id'];
        }
        if (isset($postData['manager_id'])) {
            $where['manager_id'] = $postData['manager_id'];
        }
        if (isset($postData['page'])) $where['page'] = intval($postData['page']);
        if (isset($postData['pageNum'])) $where['pageNum'] = intval($postData['pageNum']);
        if (isset($postData['pageSize'])) $where['pageSize'] = intval($postData['pageSize']);
        return $this->app->machineCheckList->getRecords($where);
    }

    /**
     * 导出小蜜蜂维护记录
     */
    public function exportRecords()
    {
        $postData = input();
        $where = [];
        if (!empty($postData['machine_id'])) {
            $where['machine_id'] = $postData['machine_id'];
        }
        if (!empty($postData['records_code'])) {
            $where['records_code'] = $postData['records_code'];
        }
        if (isset($postData['maintainer_id']) && $postData['maintainer_id'] !== '') {
            $where['maintainer_id'] = $postData['maintainer_id'];
        }
        if (isset($postData['manager_id']) && $postData['manager_id'] !== '') {
            $where['manager_id'] = $postData['manager_id'];
        }
        return $this->app->machineCheckList->exportRecords($where);
    }

    /**
     * 导出检查项列表（check_list_items）
     */
    public function exportItems()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, [
            'item_name' => 'like',
        ]);
        return $this->app->machineCheckList->exportItems($where);
    }
}
