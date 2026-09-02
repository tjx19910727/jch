<?php

namespace app\AppFactory\Kernel\Traits\FaultNotice;

use app\AppFactory\Kernel\Support\FaultNotice\FaultWechatTemplate;
use think\facade\Db;

/**
 * 故障分类、故障码目录及页面表单选项。
 */
trait FaultCatalogTrait
{
    /**
     * 分类全部同级，启用和停用均返回；“全部故障”由前端追加。
     */
    public function getFaultCatalogCategoryListData()
    {
        $rows = Db::name('machine_fault_category')
            ->alias('mfc')
            ->field(
                "mfc.category_id,mfc.category_name,mfc.template_type,mfc.status,mfc.sort," .
                "mfc.update_time," .
                "(SELECT COUNT(*) FROM machine_error_code_notice_rule mecnr " .
                "WHERE mecnr.category_id = mfc.category_id) AS fault_count"
            )
            ->order('mfc.sort asc,mfc.category_id asc')
            ->select()
            ->toArray();

        $items = array_map(function ($row) {
            return $this->formatFaultCatalogCategory($row);
        }, $rows);

        return [
            'total_fault_count' => intval(Db::name('machine_error_code_notice_rule')->count()),
            'items' => $items,
        ];
    }

    /**
     * 故障码分页返回；停用故障码也返回，便于重新启用。
     *
     * @param array $params
     * @param int $pageNum 每页条数，沿用后台现有pageNum语义
     * @return mixed
     */
    public function getFaultCatalogCodeListData($params, $pageNum = 20)
    {
        $pageNum = max(1, min(intval($pageNum), 100));
        $query = Db::name('machine_error_code_notice_rule')
            ->alias('mecnr')
            ->leftJoin(
                'machine_fault_category mfc',
                'mfc.category_id = mecnr.category_id'
            )
            ->leftJoin('machine_fault_level mfl', 'mfl.level = mecnr.level')
            ->field(
                "mecnr.error_code,mecnr.error_name,mecnr.wechat_text,mecnr.category_id," .
                "mecnr.level,mecnr.status,mecnr.notice_enabled,mecnr.update_time," .
                "COALESCE(mfc.category_name,'') AS category_name," .
                "COALESCE(mfc.status,0) AS category_status," .
                "COALESCE(mfc.template_type,'') AS template_type," .
                "COALESCE(mfl.grade,mecnr.level) AS grade," .
                "COALESCE(mfl.level_name,'') AS level_name,COALESCE(mfl.color,'') AS color"
            );

        $categoryId = intval($params['category_id'] ?? 0);
        if ($categoryId > 0) {
            if (!$this->findFaultCatalogCategory($categoryId)) {
                throw new \InvalidArgumentException('故障分类不存在');
            }
            $query->where('mecnr.category_id', $categoryId);
        }
        $level = intval($params['level'] ?? 0);
        if ($level > 0) {
            if (!in_array($level, [1, 2, 3], true)) {
                throw new \InvalidArgumentException('故障等级参数错误');
            }
            $query->where('mecnr.level', $level);
        }
        $status = intval($params['status'] ?? 0);
        if ($status > 0) {
            if (!in_array($status, [1, 2], true)) {
                throw new \InvalidArgumentException('故障码状态参数错误');
            }
            $query->where('mecnr.status', $status);
        }
        $noticeEnabled = intval($params['notice_enabled'] ?? 0);
        if ($noticeEnabled > 0) {
            if (!in_array($noticeEnabled, [1, 2], true)) {
                throw new \InvalidArgumentException('通知开关参数错误');
            }
            $query->where('mecnr.notice_enabled', $noticeEnabled);
        }
        $keyword = trim(strval($params['keyword'] ?? ''));
        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $query->where(function ($subQuery) use ($like) {
                $subQuery->where('mecnr.error_code', 'like', $like)
                    ->whereOr('mecnr.error_name', 'like', $like)
                    ->whereOr('mecnr.wechat_text', 'like', $like);
            });
        }

        $paginator = $query
            ->order('mfc.sort asc,mecnr.category_id asc,mecnr.error_code asc')
            ->paginate($pageNum, false, ['query' => request()->param()]);

        return $paginator->each(function ($row) {
            return $this->formatFaultCatalogCode($row);
        });
    }

    public function getFaultCatalogFormOptionsData()
    {
        $levels = Db::name('machine_fault_level')
            ->field('level,grade,level_name,color')
            ->whereIn('level', [1, 2, 3])
            ->order('level asc')
            ->select()
            ->toArray();
        if (!$levels) {
            $levels = [
                ['level' => 1, 'grade' => 1, 'level_name' => '紧急', 'color' => '#F5222D'],
                ['level' => 2, 'grade' => 2, 'level_name' => '一般', 'color' => '#FA8C16'],
                ['level' => 3, 'grade' => 3, 'level_name' => '提示', 'color' => '#1677FF'],
            ];
        }
        return [
            'levels' => array_map(function ($row) {
                return [
                    'level' => intval($row['level']),
                    'grade' => intval($row['grade']),
                    'level_name' => strval($row['level_name']),
                    'color' => strval($row['color']),
                ];
            }, $levels),
            'wechat_templates' => FaultWechatTemplate::options(),
        ];
    }

    public function addFaultCatalogCategoryData($params)
    {
        $data = $this->normalizeFaultCatalogCategory($params);
        $aoId = $this->getFaultCatalogAoId();
        if (Db::name('machine_fault_category')->where('category_name', $data['category_name'])->find()) {
            throw new \InvalidArgumentException('故障分类名称已存在');
        }
        $managerId = intval($this->manager['manager_id'] ?? 0);
        $now = time();
        $insert = array_merge($data, [
            'ao_id' => $aoId,
            'status' => 1,
            'creator' => $managerId,
            'create_time' => $now,
            'update_id' => $managerId,
            'update_time' => $now,
        ]);
        $categoryId = intval(Db::name('machine_fault_category')->insertGetId($insert));
        return $this->findAndFormatFaultCatalogCategory($categoryId);
    }

    public function updateFaultCatalogCategoryData($params)
    {
        $categoryId = intval($params['category_id'] ?? 0);
        $exists = $this->findFaultCatalogCategory($categoryId);
        if (!$exists) {
            throw new \InvalidArgumentException('故障分类不存在');
        }
        $data = $this->normalizeFaultCatalogCategory(array_merge($exists, $params));
        $duplicate = Db::name('machine_fault_category')
            ->where('category_name', $data['category_name'])
            ->where('category_id', '<>', $categoryId)
            ->find();
        if ($duplicate) {
            throw new \InvalidArgumentException('故障分类名称已存在');
        }
        $data['update_id'] = intval($this->manager['manager_id'] ?? 0);
        $data['update_time'] = time();
        Db::name('machine_fault_category')
            ->where('category_id', $categoryId)
            ->update($data);
        return $this->findAndFormatFaultCatalogCategory($categoryId);
    }

    public function updateFaultCatalogCategoryStatusData($categoryId, $status)
    {
        $categoryId = intval($categoryId);
        $status = intval($status);
        if (!in_array($status, [1, 2], true)) {
            throw new \InvalidArgumentException('分类状态参数错误');
        }
        $exists = $this->findFaultCatalogCategory($categoryId);
        if (!$exists) {
            throw new \InvalidArgumentException('故障分类不存在');
        }
        $update = [
            'status' => $status,
            'update_id' => intval($this->manager['manager_id'] ?? 0),
            'update_time' => time(),
        ];
        Db::name('machine_fault_category')
            ->where('category_id', $categoryId)
            ->update($update);
        return $this->findAndFormatFaultCatalogCategory($categoryId);
    }

    public function addFaultCatalogCodeData($params)
    {
        $data = $this->normalizeFaultCatalogCode($params, true);
        $aoId = $this->getFaultCatalogAoId();
        if (Db::name('machine_error_code_notice_rule')->where('error_code', $data['error_code'])->find()) {
            throw new \InvalidArgumentException('故障码已存在');
        }
        $managerId = intval($this->manager['manager_id'] ?? 0);
        $now = time();
        $insert = array_merge($data, [
            'ao_id' => $aoId,
            'creator' => $managerId,
            'create_time' => $now,
            'update_id' => $managerId,
            'update_time' => $now,
        ]);
        Db::name('machine_error_code_notice_rule')->insert($insert);
        return $this->findAndFormatFaultCatalogCode($data['error_code']);
    }

    public function updateFaultCatalogCodeData($params)
    {
        $errorCode = $this->normalizeFaultCatalogErrorCode($params['error_code'] ?? '');
        $exists = $this->findFaultCatalogCode($errorCode);
        if (!$exists) {
            throw new \InvalidArgumentException('故障码不存在');
        }
        $data = $this->normalizeFaultCatalogCode(array_merge($exists, $params), false);
        unset($data['error_code'], $data['status'], $data['notice_enabled']);
        $data['update_id'] = intval($this->manager['manager_id'] ?? 0);
        $data['update_time'] = time();
        Db::name('machine_error_code_notice_rule')->where('error_code', $errorCode)->update($data);
        return $this->findAndFormatFaultCatalogCode($errorCode);
    }

    public function updateFaultCatalogCodeSwitchData($errorCode, $field, $value)
    {
        $errorCode = $this->normalizeFaultCatalogErrorCode($errorCode);
        if (!in_array($field, ['status', 'notice_enabled'], true)) {
            throw new \InvalidArgumentException('开关字段参数错误');
        }
        $value = intval($value);
        if (!in_array($value, [1, 2], true)) {
            throw new \InvalidArgumentException('开关值参数错误');
        }
        if (!$this->findFaultCatalogCode($errorCode)) {
            throw new \InvalidArgumentException('故障码不存在');
        }
        Db::name('machine_error_code_notice_rule')->where('error_code', $errorCode)->update([
            $field => $value,
            'update_id' => intval($this->manager['manager_id'] ?? 0),
            'update_time' => time(),
        ]);
        return $this->findAndFormatFaultCatalogCode($errorCode);
    }

    /**
     * 只删除通知规则，不删除machine_error_code历史事件和解决方案。
     */
    public function deleteFaultCatalogCodeData($errorCode)
    {
        $errorCode = $this->normalizeFaultCatalogErrorCode($errorCode);
        if (!$this->findFaultCatalogCode($errorCode)) {
            throw new \InvalidArgumentException('故障码不存在');
        }
        $referenced = Db::name('machine_fault_receiver_scope')
            ->alias('mfrs')
            ->join(
                'machine_fault_receiver mfr',
                'mfr.receiver_id = mfrs.receiver_id',
                'INNER'
            )
            ->where('mfrs.scope_type', 3)
            ->where('mfrs.target_value', $errorCode)
            ->find();
        if ($referenced) {
            throw new \InvalidArgumentException('该故障码已被通知接收人引用，请先调整接收范围');
        }
        Db::name('machine_error_code_notice_rule')->where('error_code', $errorCode)->delete();
        return ['error_code' => $errorCode];
    }

    protected function getFaultCatalogAoId()
    {
        return intval($this->manager['ao_id'] ?? 0);
    }

    protected function normalizeFaultCatalogCategory($params)
    {
        $name = trim(strval($params['category_name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('故障分类名称不能为空');
        }
        if (mb_strlen($name, 'UTF-8') > 50) {
            throw new \InvalidArgumentException('故障分类名称不能超过50个字符');
        }
        $templateType = trim(strval($params['template_type'] ?? ''));
        if (!FaultWechatTemplate::isValid($templateType)) {
            throw new \InvalidArgumentException('微信故障模板类型不支持');
        }
        return [
            'category_name' => $name,
            'template_type' => $templateType,
            'sort' => max(0, intval($params['sort'] ?? 99)),
        ];
    }

    protected function normalizeFaultCatalogCode($params, $isAdd)
    {
        $errorCode = $this->normalizeFaultCatalogErrorCode($params['error_code'] ?? '');
        $errorName = trim(strval($params['error_name'] ?? ''));
        if ($errorName === '') {
            throw new \InvalidArgumentException('故障描述不能为空');
        }
        if (mb_strlen($errorName, 'UTF-8') > 255) {
            throw new \InvalidArgumentException('故障描述不能超过255个字符');
        }
        $categoryId = intval($params['category_id'] ?? 0);
        $category = $this->findFaultCatalogCategory($categoryId);
        if (!$category || intval($category['status'] ?? 0) !== 1) {
            throw new \InvalidArgumentException('请选择有效的故障分类');
        }
        $level = intval($params['level'] ?? 0);
        if (!in_array($level, [1, 2, 3], true)
            || !Db::name('machine_fault_level')->where('level', $level)->find()) {
            throw new \InvalidArgumentException('故障等级参数错误');
        }
        $status = intval($params['status'] ?? 1);
        $noticeEnabled = intval($params['notice_enabled'] ?? 1);
        if (!in_array($status, [1, 2], true)) {
            throw new \InvalidArgumentException('故障码状态参数错误');
        }
        if (!in_array($noticeEnabled, [1, 2], true)) {
            throw new \InvalidArgumentException('通知开关参数错误');
        }
        $wechatText = trim(strval($params['wechat_text'] ?? ''));
        if ($wechatText === '' && mb_strlen($errorName, 'UTF-8') <= 20) {
            $wechatText = $errorName;
        }
        if (mb_strlen($wechatText, 'UTF-8') > 20) {
            throw new \InvalidArgumentException('微信故障短名称不能超过20个字符');
        }
        if ($noticeEnabled === 1 && $wechatText === '') {
            throw new \InvalidArgumentException('开启通知时微信故障短名称不能为空');
        }

        $data = [
            'error_code' => $errorCode,
            'error_name' => $errorName,
            'wechat_text' => $wechatText,
            'category_id' => $categoryId,
            'level' => $level,
        ];
        if ($isAdd) {
            $data['status'] = $status;
            $data['notice_enabled'] = $noticeEnabled;
        }
        return $data;
    }

    protected function normalizeFaultCatalogErrorCode($errorCode)
    {
        $errorCode = trim(strval($errorCode));
        if ($errorCode === '') {
            throw new \InvalidArgumentException('故障码不能为空');
        }
        if (mb_strlen($errorCode, 'UTF-8') > 20) {
            throw new \InvalidArgumentException('故障码不能超过20个字符');
        }
        if (preg_match('/\s/u', $errorCode)) {
            throw new \InvalidArgumentException('故障码不能包含空白字符');
        }
        return $errorCode;
    }

    protected function findFaultCatalogCategory($categoryId)
    {
        $categoryId = intval($categoryId);
        if ($categoryId <= 0) {
            return [];
        }
        return (array)Db::name('machine_fault_category')
            ->where('category_id', $categoryId)
            ->find();
    }

    protected function findFaultCatalogCode($errorCode)
    {
        return (array)Db::name('machine_error_code_notice_rule')
            ->where('error_code', $errorCode)
            ->find();
    }

    protected function findAndFormatFaultCatalogCode($errorCode)
    {
        $data = $this->getFaultCatalogCodeListData(['keyword' => $errorCode]);
        foreach ($data['items'] as $item) {
            if ($item['error_code'] === $errorCode) {
                return $item;
            }
        }
        return [];
    }

    protected function findAndFormatFaultCatalogCategory($categoryId)
    {
        $row = Db::name('machine_fault_category')
            ->alias('mfc')
            ->where('mfc.category_id', intval($categoryId))
            ->field(
                "mfc.category_id,mfc.category_name,mfc.template_type,mfc.status,mfc.sort," .
                "mfc.update_time," .
                "(SELECT COUNT(*) FROM machine_error_code_notice_rule mecnr " .
                "WHERE mecnr.category_id = mfc.category_id) AS fault_count"
            )
            ->find();
        return $row ? $this->formatFaultCatalogCategory($row) : [];
    }

    protected function formatFaultCatalogCategory($row)
    {
        $templateType = strval($row['template_type'] ?? '');
        $template = FaultWechatTemplate::find($templateType);
        return [
            'category_id' => intval($row['category_id'] ?? 0),
            'category_name' => strval($row['category_name'] ?? ''),
            'template_type' => $templateType,
            'template_name' => strval($template['template_name'] ?? ''),
            'template_display_name' => strval($template['display_name'] ?? ''),
            'template_status' => FaultWechatTemplate::isValid($templateType) ? 1 : 2,
            'status' => intval($row['status'] ?? 1),
            'status_name' => intval($row['status'] ?? 1) === 1 ? '启用' : '停用',
            'sort' => intval($row['sort'] ?? 99),
            'fault_count' => intval($row['fault_count'] ?? 0),
            'update_time' => intval($row['update_time'] ?? 0),
            'update_time_text' => !empty($row['update_time'])
                ? date('Y-m-d H:i:s', intval($row['update_time']))
                : '',
        ];
    }

    protected function formatFaultCatalogCode($row)
    {
        $templateType = strval($row['template_type'] ?? '');
        $template = FaultWechatTemplate::find($templateType);
        return [
            'error_code' => strval($row['error_code'] ?? ''),
            'error_name' => strval($row['error_name'] ?? ''),
            'wechat_text' => strval($row['wechat_text'] ?? ''),
            'category_id' => intval($row['category_id'] ?? 0),
            'category_name' => strval($row['category_name'] ?? ''),
            'category_status' => intval($row['category_status'] ?? 0),
            'template_type' => $templateType,
            'template_name' => strval($template['template_name'] ?? ''),
            'template_display_name' => strval($template['display_name'] ?? ''),
            'level' => intval($row['level'] ?? 0),
            'grade' => intval($row['grade'] ?? 0),
            'level_name' => strval($row['level_name'] ?? ''),
            'color' => strval($row['color'] ?? ''),
            'status' => intval($row['status'] ?? 1),
            'status_name' => intval($row['status'] ?? 1) === 1 ? '启用' : '停用',
            'notice_enabled' => intval($row['notice_enabled'] ?? 1),
            'notice_enabled_name' => intval($row['notice_enabled'] ?? 1) === 1 ? '开启' : '关闭',
            'update_time' => intval($row['update_time'] ?? 0),
            'update_time_text' => !empty($row['update_time'])
                ? date('Y-m-d H:i:s', intval($row['update_time']))
                : '',
        ];
    }
}
