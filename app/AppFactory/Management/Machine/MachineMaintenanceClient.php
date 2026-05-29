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
     * 导出维护项目（maintenance_items）
     * @param array $where
     * @return array|\think\response\Json
     */
    public function exportItems($where = [])
    {
        try {
            $query = Db::name('maintenance_items')->where($where)->field('id,parent_id,item_name,item_level,cycle_days,description,sort_order,is_active,created_at,updated_at')->order('sort_order asc,id asc');
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
                    'cycle_days' => $item['cycle_days'] ?? '',
                    'description' => $item['description'] ?? '',
                    'sort_order' => $item['sort_order'] ?? '',
                    'is_active' => intval($item['is_active'] ?? 0) ? '启用' : '禁用',
                    'created_at' => $item['created_at'] ?? '',
                    'updated_at' => $item['updated_at'] ?? '',
                ];
            }

            $filename = '维护项目列表_' . date('YmdHis');
            $title = [
                'id' => 'ID',
                'parent_id' => '父级ID',
                'item_name' => '项目名称',
                'item_level' => '层级',
                'cycle_days' => '周期(天)',
                'description' => '描述',
                'sort_order' => '排序',
                'is_active' => '状态',
                'created_at' => '创建时间',
                'updated_at' => '更新时间',
            ];

            return $this->sendToExport('设备管理-维护项目', $filename, $title, $rows);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
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

            $list = $query
                ->leftJoin('auth_manager am', 'am.manager_id = mr.maintainer_id')
                ->field('mr.id,mr.records_code,mr.item_id,mr.machine_id,mr.maintainer_id,mr.check_status,mr.maintenance_time,mr.notes,mr.created_at,mi.item_name,mi.parent_id,mi.item_level,mi.cycle_days,IFNULL(NULLIF(am.nickname,\'\'), mr.maintainer_id) as nickname')
                ->order('mr.records_code desc,mr.id asc')
                ->select()
                ->toArray();

            $result = $this->groupMaintenanceRecords($list);
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
     * 导出设备老化维护记录
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
            $rows = $this->flattenMaintenanceRecordsForExport($groups);
            $filename = '设备老化维护记录_' . date('YmdHis');
            $title = [
                'records_code' => '记录编码',
                'machine_id' => '设备编号',
                'maintainer_id' => '维护人ID',
                'nickname' => '维护人',
                'maintenance_time' => '维护时间',
                'next_maintenance_date' => '下次维护时间',
                'item_name' => '维护项目',
                'cycle_days' => '维护周期(天)',
                'check_status_text' => '维护状态',
                'notes' => '备注',
                'created_at' => '入库时间',
            ];
            return $this->sendToExport('设备管理-设备老化维护记录', $filename, $title, $rows);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 按 records_code 分组并计算下次维护时间
     */
    protected function groupMaintenanceRecords(array $list): array
    {
        $grouped = [];
        foreach ($list as $item) {
            $code = $item['records_code'];
            if (!isset($grouped[$code])) {
                $grouped[$code] = [
                    'records_code' => $code,
                    'machine_id' => $item['machine_id'],
                    'maintainer_id' => $item['maintainer_id'],
                    'nickname' => $item['nickname'] ?? '',
                    'check_status' => $item['check_status'],
                    'maintenance_time' => $item['maintenance_time'],
                    'next_maintenance_date' => '',
                    'records' => [],
                ];
            }
            $record = [
                'id' => $item['id'],
                'item_id' => $item['item_id'],
                'item_name' => $item['item_name'],
                'parent_id' => $item['parent_id'],
                'item_level' => $item['item_level'],
                'cycle_days' => intval($item['cycle_days'] ?? 0),
                'maintainer_id' => $item['maintainer_id'],
                'nickname' => $item['nickname'] ?? '',
                'check_status' => $item['check_status'],
                'maintenance_time' => $item['maintenance_time'],
                'notes' => $item['notes'],
                'created_at' => $item['created_at'],
                'next_maintenance_date' => '',
            ];
            $record['next_maintenance_date'] = $this->calcNextMaintenanceDate(
                $record['maintenance_time'] ?: $grouped[$code]['maintenance_time'],
                $record['cycle_days']
            );
            $grouped[$code]['records'][] = $record;
            $this->refreshGroupNextMaintenanceDate($grouped[$code]);
        }
        return array_values($grouped);
    }

    /**
     * 根据维护时间与周期(天)计算下次维护日期 Y-m-d
     */
    protected function calcNextMaintenanceDate($maintenanceTime, $cycleDays): string
    {
        $cycleDays = intval($cycleDays);
        if ($cycleDays <= 0 || $maintenanceTime === null || $maintenanceTime === '') {
            return '';
        }
        $ts = is_numeric($maintenanceTime) ? intval($maintenanceTime) : strtotime((string)$maintenanceTime);
        if (!$ts) {
            return '';
        }
        return date('Y-m-d', strtotime('+' . $cycleDays . ' days', $ts));
    }

    /**
     * 汇总分组级下次维护时间（取最近需维护日期）
     */
    protected function refreshGroupNextMaintenanceDate(array &$group): void
    {
        $dates = [];
        foreach ($group['records'] as $record) {
            if (!empty($record['next_maintenance_date'])) {
                $dates[] = $record['next_maintenance_date'];
            }
        }
        $group['next_maintenance_date'] = $dates ? min($dates) : '';
    }

    /**
     * 维护记录展平为导出行
     */
    protected function flattenMaintenanceRecordsForExport(array $groups): array
    {
        $rows = [];
        foreach ($groups as $group) {
            foreach ($group['records'] as $record) {
                $rows[] = [
                    'records_code' => $group['records_code'],
                    'machine_id' => $group['machine_id'],
                    'maintainer_id' => $group['maintainer_id'],
                    'nickname' => $group['nickname'] ?? ($record['nickname'] ?? ''),
                    'maintenance_time' => $record['maintenance_time'] ?: $group['maintenance_time'],
                    'next_maintenance_date' => $record['next_maintenance_date'] ?: ($group['next_maintenance_date'] ?? ''),
                    'item_name' => $record['item_name'] ?? '',
                    'cycle_days' => $record['cycle_days'] ?? 0,
                    'check_status_text' => $this->formatCheckStatusText($record['check_status'] ?? $group['check_status'] ?? null),
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
