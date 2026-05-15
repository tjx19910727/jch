<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/5/15
 * Time: 10:30
 */

namespace app\AppFactory\Management\Machine;

use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class MachineMaintenanceClient extends ManagementClient
{
    /**
     * 获取维护项目列表
     * @param array $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return array|\think\response\Json
     */
    public function getList($where = [], $pageNum = 0, $field = '*', $order = 'sort_order asc,id asc', $rQ = 1)
    {
        $query = Db::name('maintenance_items')->where($where)->field($field)->order($order);
        if ($pageNum) {
            $list = $query->paginate($pageNum);
        } else {
            $list = $query->select();
        }
        if ($rQ) {
            return $this->rQ($list);
        }
        return $list;
    }

    /**
     * 获取单条维护项目
     * @param array $where
     * @param string $field
     * @return array|\think\response\Json
     */
    public function getFind($where = [], $field = '*', $order = '', $rQ = 1)
    {
        $query = Db::name('maintenance_items')->where($where)->field($field);
        if ($order) {
            $query = $query->order($order);
        }
        $data = $query->find();
        if ($rQ) {
            return $this->rQ($data);
        }
        return $data;
    }

    /**
     * 树形维护项目
     * @param int $active
     * @return array|\think\response\Json
     */
    public function getTree($active = 1)
    {
        $where = [];
        if ($active >= 0) {
            $where[] = ['is_active', '=', intval($active)];
        }
        $list = Db::name('maintenance_items')
            ->where($where)
            ->field('id,parent_id,item_name,item_level,cycle_days,description,sort_order,is_active,created_at,updated_at')
            ->order('sort_order asc,id asc')
            ->select()
            ->toArray();
        return $this->rQ($this->buildTree($list));
    }

    /**
     * 新增维护项目
     * @param array $postData
     * @return array|\think\response\Json
     */
    public function addItem($postData)
    {
        try {
            $data = $this->normalizeItemData($postData);
            if (isset($data['error'])) {
                return $this->rValidate($data['error']);
            }
            $id = Db::name('maintenance_items')->insertGetId($data);
            return $this->rA($id);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 更新维护项目
     * @param array $postData
     * @return array|\think\response\Json
     */
    public function updateItem($postData)
    {
        $id = intval($postData['id'] ?? 0);
        if ($id <= 0) {
            return $this->rValidate('id不能为空');
        }
        $item = Db::name('maintenance_items')->where(['id' => $id])->find();
        if (!$item) {
            return $this->rFail('维护项目不存在');
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
            $result = Db::name('maintenance_items')->where(['id' => $id])->update($data);
            return $this->rU($result);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 删除维护项目
     * @param array $postData
     * @return array|\think\response\Json
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

        $childCount = Db::name('maintenance_items')->where([['parent_id', 'in', $ids]])->count();
        if ($childCount > 0) {
            return $this->rValidate('存在子级维护项目，无法删除');
        }
        $recordCount = Db::name('maintenance_records')->where([['item_id', 'in', $ids]])->count();
        if ($recordCount > 0) {
            return $this->rValidate('存在维护记录，无法删除');
        }

        $result = Db::name('maintenance_items')->where([['id', 'in', $ids]])->delete();
        return $this->rD($result);
    }

    /**
     * 管理端：按 machine_id 或 records_code 查询维护记录（按 records_code 分组，兼容设备端返回格式）
     * @param array $where
     * @return array|\think\response\Json
     */
    public function getRecords($where = [])
    {
        try {
            $query = Db::name('maintenance_records')->alias('mr')
                ->leftJoin('maintenance_items mi', 'mi.id = mr.item_id');

            $codeQuery = Db::name('maintenance_records')->alias('mr');

            if (!empty($where['machine_id'])) {
                $query->where('mr.machine_id', $where['machine_id']);
                $codeQuery->where('mr.machine_id', $where['machine_id']);
            }
            if (!empty($where['records_code'])) {
                $query->where('mr.records_code', $where['records_code']);
                $codeQuery->where('mr.records_code', $where['records_code']);
            }
            if (isset($where['maintainer_id']) && $where['maintainer_id'] !== '') {
                $query->where('mr.maintainer_id', $where['maintainer_id']);
                $codeQuery->where('mr.maintainer_id', $where['maintainer_id']);
            }

            $page = max(1, intval($where['page'] ?? 1));
            $pageSize = intval($where['pageNum'] ?? ($where['pageSize'] ?? 0));
            $total = 0;

            if ($pageSize > 0) {
                $total = intval($codeQuery->distinct(true)->count('mr.records_code'));
                if ($total <= 0) {
                    return $this->rQ([
                        'list' => [],
                        'pagination' => [
                            'page' => $page,
                            'pageSize' => $pageSize,
                            'total' => 0,
                            'totalPage' => 0,
                        ],
                    ]);
                }

                $codes = $codeQuery->field('mr.records_code,max(mr.id) as max_id')
                    ->group('mr.records_code')
                    ->order('max_id desc')
                    ->page($page, $pageSize)
                    ->select()
                    ->column('records_code');

                if (!$codes) {
                    return $this->rQ([
                        'list' => [],
                        'pagination' => [
                            'page' => $page,
                            'pageSize' => $pageSize,
                            'total' => $total,
                            'totalPage' => (int)ceil($total / $pageSize),
                        ],
                    ]);
                }

                $query->whereIn('mr.records_code', $codes);
            }

            $list = $query->field('mr.id,mr.records_code,mr.item_id,mr.machine_id,mr.maintainer_id,mr.maintenance_time,mr.notes,mr.created_at,mi.item_name,mi.parent_id,mi.item_level')
                ->order('mr.records_code desc,mr.id asc')
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
                    'parent_id' => $item['parent_id'],
                    'item_level' => $item['item_level'],
                    'maintenance_time' => $item['maintenance_time'],
                    'notes' => $item['notes'],
                    'created_at' => $item['created_at'],
                ];
            }

            $result = array_values($grouped);
            if ($pageSize > 0) {
                return $this->rQ([
                    'list' => $result,
                    'pagination' => [
                        'page' => $page,
                        'pageSize' => $pageSize,
                        'total' => $total,
                        'totalPage' => (int)ceil($total / $pageSize),
                    ],
                ]);
            }

            return $this->rQ($result);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 整理写入字段
     * @param array $postData
     * @param bool $isUpdate
     * @return array
     */
    protected function normalizeItemData($postData, $isUpdate = false)
    {
        $allow = ['parent_id', 'item_name', 'item_level', 'cycle_days', 'description', 'sort_order', 'is_active'];
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
        if (isset($data['cycle_days']) && $data['cycle_days'] !== '') {
            $data['cycle_days'] = intval($data['cycle_days']);
        }
        if (isset($data['is_active'])) {
            $data['is_active'] = intval($data['is_active']) ? 1 : 0;
        }

        if (array_key_exists('parent_id', $data)) {
            $parentId = intval($data['parent_id']);
            if ($parentId > 0) {
                $parent = Db::name('maintenance_items')->where(['id' => $parentId])->field('id,item_level')->find();
                if (!$parent) {
                    return ['error' => '父级项目不存在'];
                }
                $data['parent_id'] = $parentId;
                $data['item_level'] = intval($parent['item_level']) + 1;
            } else {
                $data['parent_id'] = null;
                $data['item_level'] = 1;
            }
        } else if (!$isUpdate) {
            $data['parent_id'] = null;
            $data['item_level'] = intval($data['item_level'] ?? 1);
        }

        return $data;
    }

    /**
     * 平铺数据转树
     * @param array $items
     * @return array
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
}
