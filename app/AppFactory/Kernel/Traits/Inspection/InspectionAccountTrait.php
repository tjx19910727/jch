<?php

namespace app\AppFactory\Kernel\Traits\Inspection;

use think\facade\Db;

/**
 * 巡检/维护账号统一解析（巡检人员表 + 后台账号表）。
 *
 * 规则：
 *  - 账号为 6 位或 8 位数字且首位非 0；
 *  - 优先匹配 inspection_staff.staff_code；未命中再匹配 auth_manager.account 的对应后缀
 *    （6 位输入匹配后 6 位，8 位输入匹配后 8 位）；
 *  - 后台账号多命中时不做自动选择，返回 ambiguous（由调用方提示"请继续输入 8 位"）；
 *  - 落库账号统一取"用户输入的账号串"（巡检人员即 staff_code）。
 *
 * 注意：auth_manager 与 inspection_staff 的排序规则不同（0900_ai_ci / unicode_ci），
 *       必须分开查询，禁止放在同一条 SQL 中比较（会报 1267）。
 */
trait InspectionAccountTrait
{
    /**
     * 解析巡检/维护账号。
     *
     * @param string|array $input   6/8 位账号串；或 ['staff_id' => int]（H5 token 场景）
     * @param array        $options ['require_enabled' => true, 'require_not_expired' => false]
     * @return array{ok:bool,reason:string,need_more:bool,matched_count:int,matched_by:int|null,
     *               source:string|null,account:string,staff_id:int|null,manager_id:int|null,
     *               name:string,status:int|null,expire_time:int|null}
     *         reason: ok / not_found / ambiguous / disabled / expired / invalid_format
     */
    protected function resolveInspectionAccount($input, array $options = [])
    {
        $requireEnabled = array_key_exists('require_enabled', $options) ? (bool)$options['require_enabled'] : true;
        $requireNotExpired = !empty($options['require_not_expired']);

        $result = [
            'ok' => false,
            'reason' => 'not_found',
            'need_more' => false,
            'matched_count' => 0,
            'matched_by' => null,
            'source' => null,
            'account' => '',
            'staff_id' => null,
            'manager_id' => null,
            'name' => '',
            'status' => null,
            'expire_time' => null,
        ];

        // ① H5 token 场景：按巡检人员主键解析
        if (is_array($input)) {
            $staffId = intval($input['staff_id'] ?? 0);
            $result['account'] = (string)$staffId;
            if ($staffId <= 0) {
                $result['reason'] = 'invalid_format';
                return $result;
            }
            $staff = Db::name('inspection_staff')
                ->where('staff_id', $staffId)
                ->field('staff_id,staff_code,account_name,status,expire_time')
                ->find();
            return $this->formatInspectionAccountResult($staff, $result, $requireEnabled, $requireNotExpired);
        }

        $code = trim((string)$input);
        $result['account'] = $code;
        if (!preg_match('/^[1-9][0-9]{5}$/', $code) && !preg_match('/^[1-9][0-9]{7}$/', $code)) {
            $result['reason'] = 'invalid_format';
            return $result;
        }

        // ② 巡检人员表（走唯一索引 uk_staff_code）
        $staff = Db::name('inspection_staff')
            ->whereRaw('staff_code COLLATE utf8mb4_general_ci = ?', [$code])
            ->field('staff_id,staff_code,account_name,status,expire_time')
            ->find();
        if ($staff) {
            return $this->formatInspectionAccountResult($staff, $result, $requireEnabled, $requireNotExpired);
        }

        // ③ 后台账号：按输入长度匹配后缀（仅本表内比较，避免跨表 collation 冲突）
        $suffixLen = strlen($code);
        $result['matched_by'] = $suffixLen;
        $rows = Db::name('auth_manager')
            ->whereRaw("TRIM(account) <> ''")
            ->whereRaw("RIGHT(account, {$suffixLen}) COLLATE utf8mb4_general_ci = ?", [$code])
            ->field('manager_id,account,nickname,status')
            ->order('manager_id asc')
            ->select();
        $rows = is_array($rows) ? $rows : $rows->toArray();
        $result['matched_count'] = count($rows);

        if (!$rows) {
            return $result; // reason = not_found
        }
        if (count($rows) > 1) {
            // 多命中不自动选择：提示用户继续输入更完整的账号（8 位）
            $result['reason'] = 'ambiguous';
            $result['need_more'] = true;
            return $result;
        }

        $manager = $rows[0];
        $result['source'] = 'auth_manager';
        $result['manager_id'] = intval($manager['manager_id'] ?? 0);
        $result['name'] = (string)($manager['nickname'] ?? '');
        $result['status'] = intval($manager['status'] ?? 0);
        if ($requireEnabled && $result['status'] !== 1) {
            $result['reason'] = 'disabled';
            return $result;
        }
        $result['ok'] = true;
        $result['reason'] = 'ok';
        return $result;
    }

    /**
     * 组装巡检/维护记录"展示姓名"的 SQL 表达式（三处查询复用）。
     *
     * 取值优先级：巡检表账号匹配 → 巡检表主键匹配（历史） → 后台账号精确匹配 → 后台账号 8 位后缀 → 后台账号 6 位后缀 → 兜底显示账号串。
     * 多命中时取 manager_id 最小，避免张冠李戴；显式 COLLATE 规避跨表排序规则冲突（1267）。
     *
     * @param string $alias 记录表别名（默认 cr）
     * @return string
     */
    protected function inspectionPersonNameExpr($alias = 'cr')
    {
        $ist = "(SELECT ist.account_name FROM inspection_staff ist"
            . " WHERE ist.staff_code COLLATE utf8mb4_general_ci = {$alias}.manager_id"
            . "    OR ist.staff_id = {$alias}.manager_id"
            . " ORDER BY (ist.staff_code COLLATE utf8mb4_general_ci = {$alias}.manager_id) DESC, ist.staff_id ASC"
            . " LIMIT 1)";
        $am = "(SELECT am.nickname FROM auth_manager am"
            . " WHERE am.account COLLATE utf8mb4_general_ci = {$alias}.manager_id"
            . "    OR RIGHT(am.account,8) COLLATE utf8mb4_general_ci = {$alias}.manager_id"
            . "    OR RIGHT(am.account,6) COLLATE utf8mb4_general_ci = {$alias}.manager_id"
            . " ORDER BY (am.account COLLATE utf8mb4_general_ci = {$alias}.manager_id) DESC,"
            . "          (RIGHT(am.account,8) COLLATE utf8mb4_general_ci = {$alias}.manager_id) DESC,"
            . "          am.manager_id ASC"
            . " LIMIT 1)";
        return "COALESCE(NULLIF({$ist}, ''), NULLIF({$am}, ''), {$alias}.manager_id)";
    }

    /**
     * 组装巡检人员表命中结果，并做启用/过期判定。
     */
    protected function formatInspectionAccountResult($staff, array $result, $requireEnabled, $requireNotExpired)
    {
        if (!$staff) {
            $result['reason'] = 'not_found';
            return $result;
        }
        if (is_object($staff) && method_exists($staff, 'toArray')) {
            $staff = $staff->toArray();
        }
        $result['source'] = 'inspection_staff';
        $result['staff_id'] = intval($staff['staff_id'] ?? 0);
        $result['name'] = (string)($staff['account_name'] ?? '');
        $result['status'] = intval($staff['status'] ?? 0);
        $result['expire_time'] = intval($staff['expire_time'] ?? 0);

        if ($requireEnabled && $result['status'] !== 1) {
            $result['reason'] = 'disabled';
            return $result;
        }
        if ($requireNotExpired && $result['expire_time'] > 0 && $result['expire_time'] <= time()) {
            $result['reason'] = 'expired';
            return $result;
        }
        $result['ok'] = true;
        $result['reason'] = 'ok';
        return $result;
    }
}
