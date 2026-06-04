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
     * 启用/禁用检查项
     * @param array $postData
     * @return array|\think\response\Json
     */
    public function setActive($postData)
    {
        $id = $postData['id'] ?? '';
        $isActive = intval($postData['is_active'] ?? -1);
        if (!$id) {
            return $this->rValidate('id不能为空');
        }
        if (!in_array($isActive, [0, 1], true)) {
            return $this->rValidate('启用状态仅支持0或1');
        }
        $ids = is_array($id) ? $id : explode(',', strval($id));
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return $this->rValidate('id不能为空');
        }
        $ids = $isActive === 1 ? $this->collectAncestorIds($ids) : $this->collectDescendantIds($ids);
        $existsCount = Db::name('check_list_items')->where([['id', 'in', $ids]])->count();
        if ($existsCount <= 0) {
            return $this->rValidate('检查项不存在');
        }
        Db::name('check_list_items')->where([['id', 'in', $ids]])->update(['is_active' => $isActive]);
        return $this->rU($existsCount);
    }

    /**
     * 管理端：按 machine_id 或 records_code 查询检查记录（按 records_code 分组）
     */
    public function getRecords($where = [])
    {
        try {
            $query = Db::name('check_list_records')->alias('cr')
                ->leftJoin('check_list_items ci', 'ci.id = cr.item_id');

            $codeQuery = Db::name('check_list_records')->alias('cr');

            if (!empty($where['machine_id'])) {
                $query->where('cr.machine_id', $where['machine_id']);
                $codeQuery->where('cr.machine_id', $where['machine_id']);
            }
            if (!empty($where['records_code'])) {
                $query->where('cr.records_code', $where['records_code']);
                $codeQuery->where('cr.records_code', $where['records_code']);
            }
            if (isset($where['maintainer_id']) && $where['maintainer_id'] !== '') {
                $query->where('cr.manager_id', $where['maintainer_id']);
                $codeQuery->where('cr.manager_id', $where['maintainer_id']);
            }
            if (isset($where['manager_id']) && $where['manager_id'] !== '') {
                $query->where('cr.manager_id', $where['manager_id']);
                $codeQuery->where('cr.manager_id', $where['manager_id']);
            }

            $page = max(1, intval($where['page'] ?? 1));
            $pageSize = intval($where['pageNum'] ?? ($where['pageSize'] ?? 0));
            $total = 0;

            if ($pageSize > 0) {
                $total = intval($codeQuery->distinct(true)->count('cr.records_code'));
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

                $codes = $codeQuery->field('cr.records_code,max(cr.id) as max_id')
                    ->group('cr.records_code')
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

                $query->whereIn('cr.records_code', $codes);
            }

            $list = $query
                ->leftJoin('auth_manager am', 'am.manager_id = cr.manager_id')
                ->field('cr.id,cr.records_code,cr.item_id,cr.machine_id,cr.manager_id,cr.check_status,cr.check_time,cr.notes,cr.created_at,ci.item_name,ci.parent_id,ci.item_level,IFNULL(NULLIF(am.nickname,\'\'), cr.manager_id) as nickname')
                ->order('cr.records_code desc,cr.id asc')
                ->select()
                ->toArray();

            $result = $this->groupCheckListRecords($list);
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
     * 导出小蜜蜂维护记录
     * @param array $where
     * @return array|\think\response\Json
     */
    public function exportRecords($where = [])
    {
        try {
            $where['page'] = 1;
            $where['pageNum'] = 0;
            $where['pageSize'] = 0;
            $response = obj2arr($this->getRecords($where));
            if (intval($response['state'] ?? 0) !== 200) {
                return $response;
            }
            $groups = $response['data'] ?? [];
            if (!$groups) {
                return $this->rFail('暂无可导出数据');
            }
            $rows = $this->flattenCheckListRecordsForExport($groups);
            $filename = '小蜜蜂维护记录_' . date('YmdHis');
            $title = [
                'records_code' => '记录编码',
                'machine_id' => '设备编号',
                'manager_id' => '维护人ID',
                'nickname' => '维护人',
                'check_time' => '维护时间',
                'item_name' => '检查项目',
                'check_status_text' => '维护状态',
                'notes' => '备注',
                'created_at' => '入库时间',
            ];
            return $this->sendToExport('设备管理-小蜜蜂维护记录', $filename, $title, $rows);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 导出检查项列表（check_list_items）
     * @param array $where
     * @return array|\think\response\Json
     */
    public function exportItems($where = [])
    {
        try {
            $query = Db::name('check_list_items')->where($where)->field('id,parent_id,item_name,item_level,description,sort_order,is_active,created_at,updated_at')->order('sort_order asc,id asc');
            $list = $query->select()->toArray();
            if (!$list) {
                return $this->rFail('暂无可导出数据');
            }
            $rows = [];
            foreach ($list as $item) {
                $rows[] = [
                    'id' => $item['id'] ?? '',
                    'parent_id' => $item['parent_id'] ?? '',
                    'item_name' => $item['item_name'] ?? '',
                    'item_level' => $item['item_level'] ?? '',
                    'description' => $item['description'] ?? '',
                    'sort_order' => $item['sort_order'] ?? '',
                    'is_active' => intval($item['is_active'] ?? 0) ? '启用' : '禁用',
                    'created_at' => $item['created_at'] ?? '',
                    'updated_at' => $item['updated_at'] ?? '',
                ];
            }

            $filename = '检查项列表_' . date('YmdHis');
            $title = [
                'id' => 'ID',
                'parent_id' => '父级ID',
                'item_name' => '项目名称',
                'item_level' => '层级',
                'description' => '描述',
                'sort_order' => '排序',
                'is_active' => '状态',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ];

            return $this->sendToExport('设备管理-检查项', $filename, $title, $rows);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 按 records_code 分组
     */
    protected function groupCheckListRecords(array $list): array
    {
        $grouped = [];
        foreach ($list as $item) {
            $code = $item['records_code'];
            if (!isset($grouped[$code])) {
                $grouped[$code] = [
                    'records_code' => $code,
                    'machine_id' => $item['machine_id'],
                    'manager_id' => $item['manager_id'],
                    'nickname' => $item['nickname'] ?? '',
                    'check_time' => $item['check_time'],
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
                'manager_id' => $item['manager_id'],
                'nickname' => $item['nickname'] ?? '',
                'check_time' => $item['check_time'],
                'notes' => $item['notes'],
                'created_at' => $item['created_at'],
            ];
        }
        return array_values($grouped);
    }

    protected function flattenCheckListRecordsForExport(array $groups): array
    {
        $rows = [];
        foreach ($groups as $group) {
            foreach ($group['records'] as $record) {
                $rows[] = [
                    'records_code' => $group['records_code'],
                    'machine_id' => $group['machine_id'],
                    'manager_id' => $group['manager_id'],
                    'nickname' => $group['nickname'] ?? ($record['nickname'] ?? ''),
                    'check_time' => $record['check_time'] ?: $group['check_time'],
                    'item_name' => $record['item_name'] ?? '',
                    'check_status_text' => $this->formatCheckStatusText($record['check_status'] ?? null),
                    'notes' => $record['notes'] ?? '',
                    'created_at' => $record['created_at'] ?? '',
                ];
            }
        }
        return $rows;
    }

    protected function formatCheckStatusText($status): string
    {
        if ($status === null || $status === '') {
            return '';
        }
        return intval($status) === 1 ? '正常' : (intval($status) === 2 ? '异常' : strval($status));
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

    /**
     * 获取指定项目及全部下级项目ID
     * @param int[] $ids
     * @return int[]
     */
    protected function collectDescendantIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $all = $ids;
        $current = $ids;
        while ($current) {
            $children = Db::name('check_list_items')
                ->where([['parent_id', 'in', $current]])
                ->column('id');
            $children = array_values(array_diff(
                array_values(array_unique(array_map('intval', is_array($children) ? $children : []))),
                $all
            ));
            if (!$children) {
                break;
            }
            $all = array_merge($all, $children);
            $current = $children;
        }
        return array_values(array_unique($all));
    }

    /**
     * 获取指定项目及全部上级项目ID
     * @param int[] $ids
     * @return int[]
     */
    protected function collectAncestorIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        $all = $ids;
        $current = $ids;
        while ($current) {
            $parents = Db::name('check_list_items')
                ->where([['id', 'in', $current]])
                ->where('parent_id', '>', 0)
                ->column('parent_id');
            $parents = array_values(array_diff(
                array_values(array_unique(array_map('intval', is_array($parents) ? $parents : []))),
                $all
            ));
            if (!$parents) {
                break;
            }
            $all = array_merge($all, $parents);
            $current = $parents;
        }
        return array_values(array_unique($all));
    }
}
