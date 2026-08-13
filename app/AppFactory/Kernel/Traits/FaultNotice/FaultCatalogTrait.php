<?php

namespace app\AppFactory\Kernel\Traits\FaultNotice;

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
        $aoId = $this->getFaultCatalogAoId();
        $rows = Db::name('machine_fault_category')
            ->alias('mfc')
            ->leftJoin('wx_template wt', 'wt.wt_id = mfc.wt_id')
            ->where('mfc.ao_id', $aoId)
            ->field(
                "mfc.category_id,mfc.category_name,mfc.wt_id,mfc.status,mfc.sort," .
                "mfc.update_time,COALESCE(wt.template_name,'') AS template_name," .
                "COALESCE(wt.status,0) AS template_status," .
                "(SELECT COUNT(*) FROM machine_error_code_notice_rule mecnr " .
                "WHERE mecnr.ao_id = mfc.ao_id AND mecnr.category_id = mfc.category_id) AS fault_count"
            )
            ->order('mfc.sort asc,mfc.category_id asc')
            ->select()
            ->toArray();

        $items = array_map(function ($row) {
            return $this->formatFaultCatalogCategory($row);
        }, $rows);

        return [
            'total_fault_count' => intval(Db::name('machine_error_code_notice_rule')
                ->where('ao_id', $aoId)
                ->count()),
            'items' => $items,
        ];
    }

    /**
     * 故障码默认返回全部，不分页；停用故障码也返回，便于重新启用。
     */
    public function getFaultCatalogCodeListData($params)
    {
        $aoId = $this->getFaultCatalogAoId();
        $query = Db::name('machine_error_code_notice_rule')
            ->alias('mecnr')
            ->leftJoin(
                'machine_fault_category mfc',
                'mfc.ao_id = mecnr.ao_id AND mfc.category_id = mecnr.category_id'
            )
            ->leftJoin('machine_fault_level mfl', 'mfl.level = mecnr.level')
            ->leftJoin('wx_template wt', 'wt.wt_id = mfc.wt_id')
            ->where('mecnr.ao_id', $aoId)
            ->field(
                "mecnr.error_code,mecnr.error_name,mecnr.wechat_text,mecnr.category_id," .
                "mecnr.level,mecnr.status,mecnr.notice_enabled,mecnr.update_time," .
                "COALESCE(mfc.category_name,'') AS category_name," .
                "COALESCE(mfc.status,0) AS category_status,COALESCE(mfc.wt_id,0) AS wt_id," .
                "COALESCE(wt.template_name,'') AS template_name," .
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

        $rows = $query
            ->order('mfc.sort asc,mecnr.category_id asc,mecnr.error_code asc')
            ->select()
            ->toArray();
        $items = array_map(function ($row) {
            return $this->formatFaultCatalogCode($row);
        }, $rows);

        return ['total' => count($items), 'items' => $items];
    }

    public function getFaultCatalogFormOptionsData()
    {
        $aoId = $this->getFaultCatalogAoId();
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
        $templates = Db::name('wx_template')
            ->alias('wt')
            ->innerJoin('wx_official wo', 'wo.id = wt.wx_id')
            ->where('wt.ao_id', $aoId)
            ->where('wt.template_type', 'mFault')
            ->where('wt.status', 1)
            ->where('wo.status', 1)
            ->field('wt.wt_id,wt.template_name,wt.wx_id,wo.wx_name')
            ->order('wt.update_time desc,wt.wt_id desc')
            ->select()
            ->toArray();

        return [
            'levels' => array_map(function ($row) {
                return [
                    'level' => intval($row['level']),
                    'grade' => intval($row['grade']),
                    'level_name' => strval($row['level_name']),
                    'color' => strval($row['color']),
                ];
            }, $levels),
            'wechat_templates' => array_map(function ($row) {
                return [
                    'wt_id' => intval($row['wt_id']),
                    'template_name' => strval($row['template_name']),
                    'wx_id' => intval($row['wx_id']),
                    'wx_name' => strval($row['wx_name']),
                ];
            }, $templates),
        ];
    }

    public function addFaultCatalogCategoryData($params)
    {
        $data = $this->normalizeFaultCatalogCategory($params);
        $aoId = $this->getFaultCatalogAoId();
        if (Db::name('machine_fault_category')->where([
            'ao_id' => $aoId,
            'category_name' => $data['category_name'],
        ])->find()) {
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
            ->where('ao_id', $this->getFaultCatalogAoId())
            ->where('category_name', $data['category_name'])
            ->where('category_id', '<>', $categoryId)
            ->find();
        if ($duplicate) {
            throw new \InvalidArgumentException('故障分类名称已存在');
        }
        $data['update_id'] = intval($this->manager['manager_id'] ?? 0);
        $data['update_time'] = time();
        Db::name('machine_fault_category')
            ->where('ao_id', $this->getFaultCatalogAoId())
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
            ->where('ao_id', $this->getFaultCatalogAoId())
            ->where('category_id', $categoryId)
            ->update($update);
        return $this->findAndFormatFaultCatalogCategory($categoryId);
    }

    public function addFaultCatalogCodeData($params)
    {
        $data = $this->normalizeFaultCatalogCode($params, true);
        $aoId = $this->getFaultCatalogAoId();
        if (Db::name('machine_error_code_notice_rule')->where([
            'ao_id' => $aoId,
            'error_code' => $data['error_code'],
        ])->find()) {
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
        Db::name('machine_error_code_notice_rule')->where([
            'ao_id' => $this->getFaultCatalogAoId(),
            'error_code' => $errorCode,
        ])->update($data);
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
        Db::name('machine_error_code_notice_rule')->where([
            'ao_id' => $this->getFaultCatalogAoId(),
            'error_code' => $errorCode,
        ])->update([
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
            ->innerJoin('machine_fault_receiver mfr', 'mfr.receiver_id = mfrs.receiver_id')
            ->where('mfr.ao_id', $this->getFaultCatalogAoId())
            ->where('mfrs.scope_type', 3)
            ->where('mfrs.target_value', $errorCode)
            ->find();
        if ($referenced) {
            throw new \InvalidArgumentException('该故障码已被通知接收人引用，请先调整接收范围');
        }
        Db::name('machine_error_code_notice_rule')->where([
            'ao_id' => $this->getFaultCatalogAoId(),
            'error_code' => $errorCode,
        ])->delete();
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
        $wtId = intval($params['wt_id'] ?? 0);
        if (!$this->findAvailableFaultCatalogTemplate($wtId)) {
            throw new \InvalidArgumentException('微信故障模板不存在或不可用');
        }
        return [
            'category_name' => $name,
            'wt_id' => $wtId,
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
        return (array)Db::name('machine_fault_category')->where([
            'ao_id' => $this->getFaultCatalogAoId(),
            'category_id' => $categoryId,
        ])->find();
    }

    protected function findFaultCatalogCode($errorCode)
    {
        return (array)Db::name('machine_error_code_notice_rule')->where([
            'ao_id' => $this->getFaultCatalogAoId(),
            'error_code' => $errorCode,
        ])->find();
    }

    protected function findAvailableFaultCatalogTemplate($wtId)
    {
        $wtId = intval($wtId);
        if ($wtId <= 0) {
            return [];
        }
        return (array)Db::name('wx_template')
            ->alias('wt')
            ->innerJoin('wx_official wo', 'wo.id = wt.wx_id')
            ->where('wt.wt_id', $wtId)
            ->where('wt.ao_id', $this->getFaultCatalogAoId())
            ->where('wt.template_type', 'mFault')
            ->where('wt.status', 1)
            ->where('wo.status', 1)
            ->field('wt.wt_id')
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
            ->leftJoin('wx_template wt', 'wt.wt_id = mfc.wt_id')
            ->where('mfc.ao_id', $this->getFaultCatalogAoId())
            ->where('mfc.category_id', intval($categoryId))
            ->field(
                "mfc.category_id,mfc.category_name,mfc.wt_id,mfc.status,mfc.sort," .
                "mfc.update_time,COALESCE(wt.template_name,'') AS template_name," .
                "COALESCE(wt.status,0) AS template_status," .
                "(SELECT COUNT(*) FROM machine_error_code_notice_rule mecnr " .
                "WHERE mecnr.ao_id = mfc.ao_id AND mecnr.category_id = mfc.category_id) AS fault_count"
            )
            ->find();
        return $row ? $this->formatFaultCatalogCategory($row) : [];
    }

    protected function formatFaultCatalogCategory($row)
    {
        return [
            'category_id' => intval($row['category_id'] ?? 0),
            'category_name' => strval($row['category_name'] ?? ''),
            'wt_id' => intval($row['wt_id'] ?? 0),
            'template_name' => strval($row['template_name'] ?? ''),
            'template_status' => intval($row['template_status'] ?? 0),
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
        return [
            'error_code' => strval($row['error_code'] ?? ''),
            'error_name' => strval($row['error_name'] ?? ''),
            'wechat_text' => strval($row['wechat_text'] ?? ''),
            'category_id' => intval($row['category_id'] ?? 0),
            'category_name' => strval($row['category_name'] ?? ''),
            'category_status' => intval($row['category_status'] ?? 0),
            'wt_id' => intval($row['wt_id'] ?? 0),
            'template_name' => strval($row['template_name'] ?? ''),
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
