<?php

namespace app\AppFactory\Management\Inspection;

use app\AppFactory\Kernel\Traits\Inspection\InspectionStaffTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class InspectionStaffClient extends ManagementClient
{
    use InspectionStaffTrait;

    protected $field = 'staff_id,staff_code,account_name,mobile,contact_address,expire_time,ao_id,status,remark,creator,update_id,create_time,update_time';

    public function getList($where = [], $pageNum = 0, $field = '', $order = 'staff_id desc', $rQ = 1)
    {
        $list = $this->getInspectionStaffList($where, $pageNum, $field ?: $this->field, $order);
        if ($rQ) return $this->rQ($this->formatList($list));
        return $list;
    }

    public function getFind($where = [], $field = '*', $order = '', $rQ = 1)
    {
        $data = $this->formatRow($this->getInspectionStaffFind(
            $where,
            $field === '*' ? $this->field : $field,
            $order ?: 'staff_id desc'
        ));
        if ($rQ) return $this->rQ($data);
        return $data;
    }

    public function addData($postData = [])
    {
        $check = $this->checkStaffData($postData);
        if ($check !== true) return $check;
        $now = time();
        try {
            $postData['staff_code'] = ($postData['staff_code'] ?? '') ?: $this->generateStaffCode();
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rFail($e->getMessage());
        }
        $postData['ao_id'] = intval($postData['ao_id'] ?? ($this->manager['ao_id'] ?? 0));
        $postData['status'] = intval($postData['status'] ?? 1);
        $postData = $this->filterStaffFields($postData, false);
        $postData['creator'] = intval($this->manager['manager_id'] ?? 0);
        $postData['create_time'] = $now;
        $postData['update_time'] = $now;

        return $this->rA($this->addInspectionStaff($postData));
    }

    public function updateData($postData = [])
    {
        if (empty($postData['staff_id'])) return $this->rFail('巡检人员ID不能为空');
        $staffId = intval($postData['staff_id']);
        $current = $this->getInspectionStaffFind(['staff_id' => $staffId], $this->field);
        if (!$current) return $this->rFail('巡检人员不存在');
        $check = $this->checkStaffData($postData, true, $staffId);
        if ($check !== true) return $check;

        unset($postData['staff_id']);
        if (isset($postData['staff_code']) && $postData['staff_code'] === '') {
            unset($postData['staff_code']);
        }
        $postData = $this->filterStaffFields($postData, false);
        if (!$postData) return $this->rFail('没有可更新的巡检人员字段');

        $postData['update_id'] = intval($this->manager['manager_id'] ?? 0);
        $postData['update_time'] = time();

        return $this->rU($this->updateInspectionStaff($postData, ['staff_id' => $staffId]));
    }

    public function delData($staffId)
    {
        if (!$staffId) return $this->rFail('巡检人员ID不能为空');
        return $this->rD($this->delInspectionStaff(['staff_id' => intval($staffId)]));
    }

    public function export($where = [])
    {
        try {
            $list = $this->getInspectionStaffList($where, 0, $this->field, 'staff_id desc');
            $list = $list ? $list->toArray() : [];
            if (!$list) return $this->rFail('暂无可导出数据');

            $rows = [];
            foreach ($list as $item) {
                $item = $this->formatRow($item);
                $rows[] = [
                    'staff_id' => $item['staff_id'] ?? '',
                    'staff_code' => $item['staff_code'] ?? '',
                    'account_name' => $item['account_name'] ?? '',
                    'mobile' => $item['mobile'] ?? '',
                    'contact_address' => $item['contact_address'] ?? '',
                    'organization_name' => $item['organization_name'] ?? '',
                    'expire_time_text' => $item['expire_time_text'] ?? '',
                    'status_text' => $item['status_text'] ?? '',
                    'remark' => $item['remark'] ?? '',
                    'create_time_text' => $item['create_time_text'] ?? '',
                ];
            }

            return $this->sendToExport('巡检人员管理-人员列表', '巡检人员列表_' . date('YmdHis'), [
                'staff_id' => 'ID',
                'staff_code' => '巡检账号',
                'account_name' => '账户名',
                'mobile' => '手机号',
                'contact_address' => '联系地址',
                'organization_name' => '所属组织',
                'expire_time_text' => '过期时间',
                'status_text' => '状态',
                'remark' => '备注',
                'create_time_text' => '创建时间',
            ], $rows);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    protected function checkStaffData(&$data, $isUpdate = false, $staffId = 0)
    {
        if (!$isUpdate || isset($data['account_name'])) {
            if (empty($data['account_name'])) return $this->rFail('账户名不能为空');
        }
        if (isset($data['staff_code']) && $data['staff_code'] !== '') {
            if (!preg_match('/^[1-9][0-9]{5}$/', strval($data['staff_code']))) {
                return $this->rFail('巡检账号必须为首位非0的6位数字');
            }
            $where = ['staff_code' => strval($data['staff_code'])];
            if ($staffId > 0) $where[] = ['staff_id', '<>', $staffId];
            if ($this->countInspectionStaff($where)) return $this->rFail('巡检账号已存在');
        }
        if (isset($data['mobile']) && $data['mobile'] !== '' && !preg_match('/^1[3-9][0-9]{9}$/', strval($data['mobile']))) {
            return $this->rFail('手机号格式不正确');
        }
        if (isset($data['status']) && $data['status'] !== '' && !in_array(intval($data['status']), [1, 2], true)) {
            return $this->rFail('状态仅支持1启用或2禁用');
        }
        if (isset($data['expire_time']) && $data['expire_time'] !== '') {
            $data['expire_time'] = $this->normalizeTime($data['expire_time']);
            if ($data['expire_time'] <= 0) return $this->rFail('过期时间格式不正确');
        }
        return true;
    }

    protected function generateStaffCode()
    {
        for ($i = 0; $i < 50; $i++) {
            $code = strval(random_int(100000, 999999));
            if (!$this->countInspectionStaff(['staff_code' => $code])) return $code;
        }
        throw new \Exception('巡检账号生成失败，请手动输入');
    }

    protected function normalizeTime($value)
    {
        if (is_numeric($value)) return intval($value);
        $time = strtotime(strval($value));
        return $time ?: 0;
    }

    protected function filterStaffFields(array $data, $includeMeta = true)
    {
        $allow = [
            'staff_code',
            'account_name',
            'mobile',
            'contact_address',
            'expire_time',
            'ao_id',
            'status',
            'remark',
        ];
        if ($includeMeta) {
            $allow = array_merge($allow, [
                'creator',
                'update_id',
                'create_time',
                'update_time',
            ]);
        }
        return array_intersect_key($data, array_flip($allow));
    }

    protected function formatList($list)
    {
        if (is_object($list) && method_exists($list, 'each')) {
            return $list->each(function ($item) {
                return $this->formatRow($item);
            });
        }
        if (is_object($list) && method_exists($list, 'toArray')) {
            $list = $list->toArray();
        }
        if (is_array($list)) {
            foreach ($list as &$item) $item = $this->formatRow($item);
            unset($item);
        }
        return $list;
    }

    protected function formatRow($row)
    {
        if (!$row) return $row;
        if (is_object($row) && method_exists($row, 'toArray')) $row = $row->toArray();
        $status = intval($row['status'] ?? 0);
        $row['status_text'] = $status === 1 ? '启用' : ($status === 2 ? '禁用' : '未知');
        $row['expire_time_text'] = !empty($row['expire_time']) ? date('Y-m-d H:i:s', intval($row['expire_time'])) : '';
        $row['create_time_text'] = !empty($row['create_time']) ? date('Y-m-d H:i:s', intval($row['create_time'])) : '';
        $row['update_time_text'] = !empty($row['update_time']) ? date('Y-m-d H:i:s', intval($row['update_time'])) : '';
        if (!isset($row['organization_name']) && isset($row['ao_id'])) {
            $row['organization_name'] = Db::name('auth_organization')
                ->where(['ao_id' => intval($row['ao_id'])])
                ->value('organization_name') ?: '';
        }
        if (!isset($row['creator_nickname']) && isset($row['creator'])) {
            $row['creator_nickname'] = Db::name('auth_manager')
                ->where(['manager_id' => intval($row['creator'])])
                ->value('nickname') ?: '';
        }
        return $row;
    }
}
