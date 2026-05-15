<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/5/15
 * Time: 14:20
 */

namespace app\AppFactory\Management\Machine;

use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class MachineCheckListClient extends ManagementClient
{
    /**
     * 获取检查项列表
     */
    public function getList($where = [], $pageNum = 0, $field = '*', $order = 'sort_order asc,id asc', $rQ = 1)
    {
        $query = Db::name('check_list_items')->where($where)->field($field)->order($order);
        $list = $pageNum ? $query->paginate($pageNum) : $query->select();
        return $rQ ? $this->rQ($list) : $list;
    }

    /**
     * 获取单条检查项
     */
    public function getFind($where = [], $field = '*', $order = '', $rQ = 1)
    {
        $query = Db::name('check_list_items')->where($where)->field($field);
        if ($order) {
            $query = $query->order($order);
        }
        $data = $query->find();
        return $rQ ? $this->rQ($data) : $data;
    }

    /**
     * 树形检查项（一级固定：基础状态/商品陈列/核心功能）
     */
    public function getTree($active = 1)
    {
        $where = [];
        if ($active >= 0) {
            $where[] = ['is_active', '=', intval($active)];
        }
        $list = Db::name('check_list_items')
            ->where($where)
            ->field('id,parent_id,item_name,item_level,description,sort_order,is_active,created_at,updated_at')
            ->order('sort_order asc,id asc')
            ->select()
            ->toArray();

        $tree = $this->buildTree($list);
        $tree = $this->mergeDefaultRootNodes($tree);
        return $this->rQ($tree);
    }

    /**
     * 新增检查项
     */
    public function addItem($postData)
    {
        try {
            $data = $this->normalizeItemData($postData);
            if (isset($data['error'])) {
                return $this->rValidate($data['error']);
            }
            $id = Db::name('check_list_items')->insertGetId($data);
            return $this->rA($id);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 更新检查项
     */
    public function updateItem($postData)
    {
        $id = intval($postData['id'] ?? 0);
        if ($id <= 0) {
            return $this->rValidate('id不能为空');
        }
        $item = Db::name('check_list_items')->where(['id' => $id])->find();
        if (!$item) {
            return $this->rFail('检查项不存在');
        }
        if (isset($postData['parent_id']) && intval($postData['parent_id']) === $id) {
            return $this->rValidate('父级项目不能为自己');
        }
        try {
            $data = $this->normalizeItemData($postData, true);
            if (isset($data['error'])) {
                return $this->rValidate($data['error']);
            }
            if (!$data) {
                return $this->rValidate('无可更新字段');
            }
            $result = Db::name('check_list_items')->where(['id' => $id])->update($data);
            return $this->rU($result);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 删除检查项
     */
    public function delItem($postData)
    {
        $id = $postData['id'] ?? '';
        if (!$id) {
            return $this->rValidate('id不能为空');
        }
        $ids = is_array($id) ? $id : explode(',', strval($id));
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return $this->rValidate('id不能为空');
        }

        $childCount = Db::name('check_list_items')->where([['parent_id', 'in', $ids]])->count();
        if ($childCount > 0) {
            return $this->rValidate('存在子级检查项，无法删除');
        }
        $recordCount = Db::name('check_list_records')->where([['item_id', 'in', $ids]])->count();
        if ($recordCount > 0) {
            return $this->rValidate('存在检查记录，无法删除');
        }

        $result = Db::name('check_list_items')->where([['id', 'in', $ids]])->delete();
        return $this->rD($result);
    }

    /**
     * 管理端：按 machine_id 或 records_code 查询检查记录（按 records_code 分组）
     */
    public function getRecords($where = [])
    {
        try {
            $query = Db::name('check_list_records')->alias('cr')
                ->leftJoin('check_list_items ci', 'ci.id = cr.item_id');

            if (!empty($where['machine_id'])) {
                $query->where('cr.machine_id', $where['machine_id']);
            }
            if (!empty($where['records_code'])) {
                $query->where('cr.records_code', $where['records_code']);
            }
            if (isset($where['manager_id']) && $where['manager_id'] !== '') {
                $query->where('cr.manager_id', $where['manager_id']);
            }

            $list = $query->field('cr.id,cr.records_code,cr.item_id,cr.machine_id,cr.manager_id,cr.check_status,cr.check_time,cr.notes,cr.created_at,ci.item_name,ci.parent_id,ci.item_level')
                ->order('cr.records_code desc,cr.id asc')
                ->select()
                ->toArray();

            $grouped = [];
            foreach ($list as $item) {
                $code = $item['records_code'];
                if (!isset($grouped[$code])) {
                    $grouped[$code] = [
                        'records_code' => $code,
                        'machine_id' => $item['machine_id'],
                        'maintainer_id' => $item['maintainer_id'],
                        'maintenance_time' => $item['maintenance_time'],
                        'records' => [],
                    ];
                }
                $grouped[$code]['records'][] = [
                    'id' => $item['id'],
                    'item_id' => $item['item_id'],
                    'item_name' => $item['item_name'],
                    'check_status' => intval($item['check_status'] ?? 0),
                    'parent_id' => $item['parent_id'],
                    'item_level' => $item['item_level'],
                    'maintenance_time' => $item['maintenance_time'],
                    'notes' => $item['notes'],
                    'created_at' => $item['created_at'],
                ];
            }

            return $this->rQ(array_values($grouped));
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 整理写入字段
     */
    protected function normalizeItemData($postData, $isUpdate = false)
    {
        $allow = ['parent_id', 'item_name', 'item_level', 'description', 'sort_order', 'is_active'];
        $data = [];
        foreach ($allow as $field) {
            if (array_key_exists($field, $postData)) {
                $data[$field] = $postData[$field];
            }
        }
        if (!$isUpdate && !isset($data['item_name'])) {
            return ['error' => '项目名称不能为空'];
        }

        if (isset($data['item_name'])) {
            $data['item_name'] = trim($data['item_name']);
        }
        if (isset($data['description'])) {
            $data['description'] = trim(strval($data['description']));
        }
        if (isset($data['sort_order'])) {
            $data['sort_order'] = intval($data['sort_order']);
        }
        if (isset($data['is_active'])) {
            $data['is_active'] = intval($data['is_active']) ? 1 : 0;
        }

        if (array_key_exists('parent_id', $data)) {
            $parentId = intval($data['parent_id']);
            if ($parentId > 0) {
                $parent = Db::name('check_list_items')->where(['id' => $parentId])->field('id,item_level')->find();
                if (!$parent) {
                    return ['error' => '父级项目不存在'];
                }
                $data['parent_id'] = $parentId;
                $data['item_level'] = intval($parent['item_level']) + 1;
            } else {
                $data['parent_id'] = null;
                $data['item_level'] = 1;
            }
        } elseif (!$isUpdate) {
            $data['parent_id'] = null;
            $data['item_level'] = intval($data['item_level'] ?? 1);
        }

        return $data;
    }

    /**
     * 平铺数据转树
     */
    protected function buildTree($items)
    {
        $nodes = [];
        foreach ($items as $item) {
            $item['children'] = [];
            $nodes[$item['id']] = $item;
        }

        $tree = [];
        foreach ($nodes as $id => $item) {
            $parentId = intval($item['parent_id']);
            if ($parentId > 0 && isset($nodes[$parentId])) {
                $nodes[$parentId]['children'][] = &$nodes[$id];
            } else {
                $tree[] = &$nodes[$id];
            }
        }
        return $tree;
    }

    /**
     * 固定一级项目顺序
     */
    protected function mergeDefaultRootNodes($tree)
    {
        $defaultNames = ['基础状态', '商品陈列', '核心功能'];
        $rootByName = [];
        $otherRoots = [];
        foreach ($tree as $node) {
            $name = trim(strval($node['item_name'] ?? ''));
            if (in_array($name, $defaultNames, true)) {
                $rootByName[$name] = $node;
            } else {
                $otherRoots[] = $node;
            }
        }

        $result = [];
        foreach ($defaultNames as $index => $name) {
            if (isset($rootByName[$name])) {
                $result[] = $rootByName[$name];
            } else {
                $result[] = [
                    'id' => 0,
                    'parent_id' => null,
                    'item_name' => $name,
                    'item_level' => 1,
                    'description' => '',
                    'sort_order' => $index + 1,
                    'is_active' => 1,
                    'created_at' => '',
                    'updated_at' => '',
                    'children' => [],
                ];
            }
        }

        return array_merge($result, $otherRoots);
    }
}
