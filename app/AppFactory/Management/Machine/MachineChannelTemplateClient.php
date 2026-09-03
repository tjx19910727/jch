<?php

namespace app\AppFactory\Management\Machine;

use app\AppFactory\Kernel\Traits\Machine\MachineTemplateApiResponseTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class MachineChannelTemplateClient extends ManagementClient
{
    use MachineTemplateApiResponseTrait;

    const ACTIVE = 2;
    const DELETED = 1;

    public function getList($where = [], $pageNum = 0, $field = "*", $order = "", $rQ = 1)
    {
        $postData = input();
        $page = max(1, intval($postData['page'] ?? 1));
        $pageSize = intval($postData['page_size'] ?? 20);
        $pageSize = min(100, max(10, $pageSize));
        $keyword = trim((string)($postData['keyword'] ?? ''));
        $status = (string)($postData['status'] ?? 'all');
        if (!in_array($status, ['all', 'pending', 'saved'], true)) {
            return $this->templateApiError(400, 'status 只支持 all、pending、saved');
        }

        try {
            $countQuery = $this->newTemplateListQuery($keyword);
            $counts = $countQuery->field(
                'COUNT(*) AS all_count,'
                . 'SUM(CASE WHEN s.scheme_id IS NULL THEN 1 ELSE 0 END) AS pending_count,'
                . 'SUM(CASE WHEN s.scheme_id IS NOT NULL THEN 1 ELSE 0 END) AS saved_count'
            )->find();

            $listQuery = $this->newTemplateListQuery($keyword);
            if ($status === 'pending') {
                $listQuery->whereNull('s.scheme_id');
            } elseif ($status === 'saved') {
                $listQuery->whereNotNull('s.scheme_id');
            }

            $total = $status === 'all'
                ? intval($counts['all_count'] ?? 0)
                : intval($counts[$status . '_count'] ?? 0);

            $rows = $listQuery
                ->field($this->templateListFields())
                ->order('recent_updated_at desc,t.template_id desc')
                ->page($page, $pageSize)
                ->select();

            $list = [];
            foreach ($rows as $row) {
                $list[] = $this->formatTemplate($row);
            }

            return $this->templateApiSuccess([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'status_counts' => [
                    'all' => intval($counts['all_count'] ?? 0),
                    'pending' => intval($counts['pending_count'] ?? 0),
                    'saved' => intval($counts['saved_count'] ?? 0),
                ],
            ]);
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->templateApiError(500, '查询货道模板失败');
        }
    }

    public function getDetail()
    {
        $templateId = intval(input('template_id'));
        if ($templateId <= 0) {
            return $this->templateApiError(400, 'template_id 参数错误');
        }

        try {
            $query = $this->newTemplateListQuery('');
            $row = $query
                ->where('t.template_id', $templateId)
                ->field($this->templateListFields())
                ->find();
            if (!$row) {
                return $this->templateApiError(404, '货道模板不存在');
            }
            return $this->templateApiSuccess($this->formatTemplate($row));
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->templateApiError(500, '查询货道模板失败');
        }
    }

    public function save()
    {
        $postData = input();
        $templateId = intval($postData['template_id'] ?? 0);
        $validation = $this->validateTemplatePayload($postData);
        if (!$validation['valid']) {
            return $this->templateApiError(400, $validation['message']);
        }
        $saveData = $validation['data'];
        $aoId = $this->currentAoId();
        $managerId = intval($this->manager['manager_id'] ?? 0);
        $now = time();

        Db::startTrans();
        try {
            if ($templateId <= 0) {
                if ($this->templateNameExists($saveData['template_name'], $aoId)) {
                    Db::rollback();
                    return $this->templateApiError(409, '同一组织内模板名称不能重复');
                }
                $saveData += [
                    'template_code' => $this->generateCode('TPL'),
                    'ao_id' => $aoId,
                    'template_version' => 1,
                    'creator' => $managerId,
                    'update_id' => $managerId,
                    'is_del' => self::ACTIVE,
                    'create_time' => $now,
                    'update_time' => $now,
                ];
                $templateId = intval(Db::name('machine_channel_template')->insertGetId($saveData));
                Db::commit();
                return $this->templateApiSuccess([
                    'template_id' => $templateId,
                    'template_code' => $saveData['template_code'],
                    'template_version' => 1,
                    'status' => 'pending',
                    'updated_at' => $now,
                ]);
            }

            $requestVersion = intval($postData['template_version'] ?? 0);
            if ($requestVersion <= 0) {
                Db::rollback();
                return $this->templateApiError(400, '修改模板必须提交 template_version');
            }

            $existing = $this->findTemplate($templateId, true);
            if (!$existing) {
                Db::rollback();
                return $this->templateApiError(404, '货道模板不存在');
            }
            if (intval($existing['template_version']) !== $requestVersion) {
                Db::rollback();
                return $this->templateApiError(409, '模板已被其他用户修改，请刷新后重试');
            }
            if ($this->templateNameExists($saveData['template_name'], intval($existing['ao_id']), $templateId)) {
                Db::rollback();
                return $this->templateApiError(409, '同一组织内模板名称不能重复');
            }

            $geometryChanged = $this->geometryChanged($existing, $saveData);
            $newVersion = $requestVersion + 1;
            $updateData = $saveData + [
                'template_version' => $newVersion,
                'update_id' => $managerId,
                'update_time' => $now,
            ];

            $updateQuery = Db::name('machine_channel_template')
                ->where('template_id', $templateId)
                ->where('template_version', $requestVersion)
                ->where('is_del', self::ACTIVE);
            $this->applyOrganizationScope($updateQuery, '', intval($existing['ao_id']));
            if (!$updateQuery->update($updateData)) {
                Db::rollback();
                return $this->templateApiError(409, '模板版本冲突，请刷新后重试');
            }

            if ($geometryChanged) {
                Db::name('machine_loading_scheme')->where('template_id', $templateId)->delete();
                $status = 'pending';
            } else {
                Db::name('machine_loading_scheme')
                    ->where('template_id', $templateId)
                    ->update([
                        'template_version' => $newVersion,
                        'update_id' => $managerId,
                    ]);
                $status = Db::name('machine_loading_scheme')->where('template_id', $templateId)->find()
                    ? 'saved'
                    : 'pending';
            }

            Db::commit();
            return $this->templateApiSuccess([
                'template_id' => $templateId,
                'template_code' => (string)$existing['template_code'],
                'template_version' => $newVersion,
                'status' => $status,
                'updated_at' => $now,
            ]);
        } catch (\Throwable $e) {
            Db::rollback();
            actionException($e, 1);
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return $this->templateApiError(409, '模板编号或同一组织内模板名称重复');
            }
            return $this->templateApiError(500, '保存货道模板失败');
        }
    }

    public function del($where = [], $rD = 1)
    {
        $templateId = intval(input('template_id'));
        $templateVersion = intval(input('template_version'));
        if ($templateId <= 0 || $templateVersion <= 0) {
            return $this->templateApiError(400, 'template_id 和 template_version 参数错误');
        }

        Db::startTrans();
        try {
            $template = $this->findTemplate($templateId, true);
            if (!$template) {
                Db::rollback();
                return $this->templateApiError(404, '货道模板不存在');
            }
            if (intval($template['template_version']) !== $templateVersion) {
                Db::rollback();
                return $this->templateApiError(409, '模板版本冲突，请刷新后重试');
            }

            $query = Db::name('machine_channel_template')
                ->where('template_id', $templateId)
                ->where('template_version', $templateVersion)
                ->where('is_del', self::ACTIVE);
            $this->applyOrganizationScope($query, '', intval($template['ao_id']));
            if (!$query->update([
                'is_del' => self::DELETED,
                'template_version' => $templateVersion + 1,
                'update_id' => intval($this->manager['manager_id'] ?? 0),
                'update_time' => time(),
            ])) {
                Db::rollback();
                return $this->templateApiError(409, '模板版本冲突，请刷新后重试');
            }
            Db::name('machine_loading_scheme')->where('template_id', $templateId)->delete();
            Db::commit();
            return $this->templateApiSuccess(['template_id' => $templateId]);
        } catch (\Throwable $e) {
            Db::rollback();
            actionException($e, 1);
            return $this->templateApiError(500, '删除货道模板失败');
        }
    }

    protected function newTemplateListQuery($keyword)
    {
        $query = Db::name('machine_channel_template')
            ->alias('t')
            ->leftJoin('machine_loading_scheme s', 's.template_id = t.template_id')
            ->where('t.is_del', self::ACTIVE);
        $this->applyOrganizationScope($query, 't.');
        if ($keyword !== '') {
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->whereLike('t.template_name', '%' . $keyword . '%')
                    ->whereLike('t.template_code', '%' . $keyword . '%', 'OR');
            });
        }
        return $query;
    }

    protected function templateListFields()
    {
        return 't.template_id,t.template_code,t.template_name,t.machine_type,'
            . 't.width,t.height,t.depth,t.horizontal_gap,t.depth_gap,t.shelf_gap,t.shelf_thickness,'
            . 't.remark,t.template_version,t.create_time,t.update_time,'
            . 's.scheme_id,s.scheme_code,s.scheme_name,s.scheme_version,s.total_capacity,'
            . 's.shelf_count,s.front_row_count,s.sku_count,s.width_utilization,s.update_time AS saved_at,'
            . 'GREATEST(t.update_time,IFNULL(s.update_time,0)) AS recent_updated_at';
    }

    protected function formatTemplate($row)
    {
        $saved = intval($row['scheme_id'] ?? 0) > 0;
        return [
            'template_id' => intval($row['template_id']),
            'template_code' => (string)$row['template_code'],
            'template_name' => (string)$row['template_name'],
            'machine_type' => (string)$row['machine_type'],
            'width' => intval($row['width']),
            'height' => intval($row['height']),
            'depth' => intval($row['depth']),
            'horizontal_gap' => intval($row['horizontal_gap']),
            'depth_gap' => intval($row['depth_gap']),
            'shelf_gap' => intval($row['shelf_gap']),
            'shelf_thickness' => intval($row['shelf_thickness']),
            'remark' => (string)$row['remark'],
            'status' => $saved ? 'saved' : 'pending',
            'progress_step' => $saved ? 3 : 1,
            'progress_total' => 3,
            'template_version' => intval($row['template_version']),
            'created_at' => intval($row['create_time']),
            'updated_at' => intval($row['recent_updated_at'] ?? $row['update_time']),
            'scheme_summary' => $saved ? [
                'scheme_id' => intval($row['scheme_id']),
                'scheme_code' => (string)$row['scheme_code'],
                'scheme_name' => (string)$row['scheme_name'],
                'scheme_version' => intval($row['scheme_version']),
                'total_capacity' => intval($row['total_capacity']),
                'shelf_count' => intval($row['shelf_count']),
                'front_row_count' => intval($row['front_row_count']),
                'sku_count' => intval($row['sku_count']),
                'width_utilization' => round(floatval($row['width_utilization']), 2),
                'saved_at' => intval($row['saved_at']),
            ] : null,
        ];
    }

    protected function validateTemplatePayload(array $data)
    {
        $templateName = trim((string)($data['template_name'] ?? ''));
        if ($templateName === '' || mb_strlen($templateName) > 40) {
            return $this->invalid('template_name 长度必须为 1～40 个字符');
        }
        $machineType = (string)($data['machine_type'] ?? '');
        if (!in_array($machineType, ['basic', 'luxury'], true)) {
            return $this->invalid('machine_type 只支持 basic、luxury');
        }

        $ranges = [
            'width' => [180, 1600],
            'height' => [120, 1800],
            'depth' => [150, 1200],
            'horizontal_gap' => [0, 100],
            'depth_gap' => [0, 50],
            'shelf_gap' => [0, 100],
            'shelf_thickness' => [0, 60],
        ];
        $result = [
            'template_name' => $templateName,
            'machine_type' => $machineType,
        ];
        foreach ($ranges as $field => $range) {
            if (!$this->integerInRange($data[$field] ?? null, $range[0], $range[1])) {
                return $this->invalid($field . ' 必须是 ' . $range[0] . '～' . $range[1] . ' 的整数');
            }
            $result[$field] = intval($data[$field]);
        }
        $remark = trim((string)($data['remark'] ?? ''));
        if (mb_strlen($remark) > 100) {
            return $this->invalid('remark 最多 100 个字符');
        }
        $result['remark'] = $remark;
        return ['valid' => true, 'message' => '', 'data' => $result];
    }

    protected function invalid($message)
    {
        return ['valid' => false, 'message' => $message, 'data' => []];
    }

    protected function integerInRange($value, $min, $max)
    {
        return is_numeric($value)
            && floor(floatval($value)) == floatval($value)
            && intval($value) >= $min
            && intval($value) <= $max;
    }

    protected function findTemplate($templateId, $lock = false)
    {
        $query = Db::name('machine_channel_template')
            ->where('template_id', intval($templateId))
            ->where('is_del', self::ACTIVE);
        $this->applyOrganizationScope($query);
        if ($lock) {
            $query->lock(true);
        }
        return $query->find();
    }

    protected function templateNameExists($templateName, $aoId, $excludeTemplateId = 0)
    {
        $query = Db::name('machine_channel_template')
            ->where('ao_id', intval($aoId))
            ->where('template_name', $templateName)
            ->where('is_del', self::ACTIVE);
        if ($excludeTemplateId > 0) {
            $query->where('template_id', '<>', intval($excludeTemplateId));
        }
        return (bool)$query->find();
    }

    protected function geometryChanged(array $existing, array $newData)
    {
        foreach (['machine_type', 'width', 'height', 'depth', 'horizontal_gap', 'depth_gap', 'shelf_gap', 'shelf_thickness'] as $field) {
            if ((string)$existing[$field] !== (string)$newData[$field]) {
                return true;
            }
        }
        return false;
    }

    protected function applyOrganizationScope($query, $prefix = '', $knownAoId = null)
    {
        $aoId = $this->currentAoId();
        if ($aoId > 1) {
            $query->where($prefix . 'ao_id', $knownAoId === null ? $aoId : intval($knownAoId));
        }
        return $query;
    }

    protected function currentAoId()
    {
        return intval($this->manager['ao_id'] ?? 0);
    }

    protected function generateCode($prefix)
    {
        return $prefix . '-' . strtolower(base_convert((string)time(), 10, 36)) . '-' . bin2hex(random_bytes(5));
    }
}
