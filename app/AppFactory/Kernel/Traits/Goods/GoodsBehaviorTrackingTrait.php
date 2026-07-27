<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/24
 * Time: 0:00
 */

namespace app\AppFactory\Kernel\Traits\Goods;

use app\AppFactory\Kernel\Model\Goods\GoodsBehaviorTrackingModel;
use think\facade\Db;

trait GoodsBehaviorTrackingTrait
{
    public function getGoodsBehaviorTrackingCount($where)
    {
        return GoodsBehaviorTrackingModel::getCount($where, "gbt_id");
    }

    public function getGoodsBehaviorTrackingFind($where, $field = "*", $order = "")
    {
        return GoodsBehaviorTrackingModel::getFind($where, $field, $order);
    }

    public function getGoodsBehaviorTrackingList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return GoodsBehaviorTrackingModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
    }

    public function addGoodsBehaviorTracking($insert)
    {
        return GoodsBehaviorTrackingModel::insertOneGetId($insert);
    }

    public function updateGoodsBehaviorTracking($update, $where = [], $field = [])
    {
        return GoodsBehaviorTrackingModel::update($update, $where, $field);
    }

    public function delGoodsBehaviorTracking($where)
    {
        return GoodsBehaviorTrackingModel::whereDel($where);
    }

    /**
     * 从 goods_behavior_tracking 表按条件汇总 click_count
     * @param array $where  业务侧的完整 $where 条件
     * @return int
     */
    public function getBehaviorClickSum($where)
    {
        $cutoverSql = $this->getBehaviorVersionCutoverSubQuery();
        $newClickSum = Db::name('goods_behavior_tracking')->alias('gbt')
            ->leftJoin('machine m', 'm.m_id = gbt.m_id')
            ->leftJoin([$cutoverSql => 'bvc'], 'bvc.m_id = gbt.m_id')
            ->where($where)
            ->whereRaw(
                'bvc.switch_time IS NOT NULL '
                . 'AND COALESCE(NULLIF(gbt.device_created_at, 0), UNIX_TIMESTAMP(gbt.report_date)) >= bvc.switch_time'
            )
            ->sum('gbt.click_count');

        $oldWhere = $this->formatOldGoodsHitBehaviorWhere($where);
        $oldClickSum = Db::name('goods_hit')->alias('gh')
            ->leftJoin('machine m', 'm.m_id = gh.m_id')
            ->leftJoin([$cutoverSql => 'bvc'], 'bvc.m_id = gh.m_id')
            ->where($oldWhere)
            ->whereRaw('(bvc.switch_time IS NULL OR gh.create_time < bvc.switch_time)')
            ->count('gh.gh_id');

        return intval($oldClickSum) + intval($newClickSum);
    }

    /**
     * Get versioned clicks grouped by machine and goods for list/export rows.
     */
    protected function getBehaviorClickGroupMap($where)
    {
        $cutoverSql = $this->getBehaviorVersionCutoverSubQuery();
        $oldQuery = Db::name('goods_hit')->alias('gh')
            ->leftJoin('machine m', 'm.m_id = gh.m_id')
            ->leftJoin([$cutoverSql => 'bvc'], 'bvc.m_id = gh.m_id')
            ->where($this->formatOldGoodsHitBehaviorWhere($where))
            ->whereRaw('(bvc.switch_time IS NULL OR gh.create_time < bvc.switch_time)')
            ->field(
                "CONVERT(gh.machine_id USING utf8mb4) COLLATE utf8mb4_unicode_ci machine_id,"
                . 'gh.g_id goods_id,COUNT(gh.gh_id) total_click'
            )
            ->group('gh.machine_id,gh.g_id');

        $newQuery = Db::name('goods_behavior_tracking')->alias('gbt')
            ->leftJoin('machine m', 'm.m_id = gbt.m_id')
            ->leftJoin([$cutoverSql => 'bvc'], 'bvc.m_id = gbt.m_id')
            ->where($where)
            ->whereRaw(
                'bvc.switch_time IS NOT NULL '
                . 'AND COALESCE(NULLIF(gbt.device_created_at, 0), UNIX_TIMESTAMP(gbt.report_date)) >= bvc.switch_time'
            )
            ->field(
                "CONVERT(gbt.machine_id USING utf8mb4) COLLATE utf8mb4_unicode_ci machine_id,"
                . 'gbt.goods_id,SUM(gbt.click_count) total_click'
            )
            ->group('gbt.machine_id,gbt.goods_id');

        $oldQuery->unionAll($newQuery->buildSql(false));
        $rows = Db::table($oldQuery->buildSql() . ' behavior_clicks')
            ->field('machine_id,goods_id,SUM(total_click) total_click')
            ->group('machine_id,goods_id')
            ->select();

        $clickMap = [];
        foreach ($rows as $row) {
            $machineId = strval($row['machine_id'] ?? '');
            $goodsId = intval($row['goods_id'] ?? 0);
            $clickMap[$machineId][$goodsId] = intval($row['total_click'] ?? 0);
        }
        return $clickMap;
    }

    /**
     * Find the first successful upgrade to 0.8.200 or a later 0.x version.
     * Prefixes such as LTS-0.8.201 are supported because only dot segments
     * two and three are compared.
     */
    protected function getBehaviorVersionCutoverSubQuery()
    {
        $minorVersion = "CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(version_no, '.', 2), '.', -1) AS UNSIGNED)";
        $patchVersion = "CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(version_no, '.', 3), '.', -1) AS UNSIGNED)";

        return Db::name('machine_version_plan')
            ->where('status', 2)
            ->where('update_time', '>', 0)
            ->whereRaw("version_no LIKE '%.%.%'")
            ->whereRaw("({$minorVersion} > 8 OR ({$minorVersion} = 8 AND {$patchVersion} >= 200))")
            ->field('m_id,MIN(update_time) switch_time')
            ->group('m_id')
            ->buildSql();
    }

    /**
     * Map behavior-table filters to the legacy goods_hit table.
     */
    protected function formatOldGoodsHitBehaviorWhere($where)
    {
        $oldWhere = [];
        foreach ($where as $key => $value) {
            if (!is_array($value)) {
                if ($key === 'gbt.is_online' || $key === 'is_online') {
                    if (intval($value) !== 2) {
                        $oldWhere[] = ['gh.gh_id', '=', 0];
                    }
                    continue;
                }
                $field = $this->mapOldGoodsHitBehaviorField($key);
                if ($field !== '') {
                    $oldWhere[$field] = $value;
                }
                continue;
            }

            $field = $value[0] ?? '';
            if ($field === 'gbt.is_online' || $field === 'is_online') {
                if (!$this->oldGoodsHitMatchesOnlineCondition($value)) {
                    $oldWhere[] = ['gh.gh_id', '=', 0];
                }
                continue;
            }

            $value[0] = $this->mapOldGoodsHitBehaviorField($field);
            if ($value[0] !== '') {
                $oldWhere[] = $value;
            }
        }

        return $oldWhere;
    }

    protected function mapOldGoodsHitBehaviorField($field)
    {
        $fieldMap = [
            'gbt.m_id' => 'gh.m_id',
            'gbt.machine_id' => 'gh.machine_id',
            'gbt.goods_id' => 'gh.g_id',
            'gbt.device_created_at' => 'gh.create_time',
            'gbt.report_date' => 'gh.create_date',
            'm_id' => 'gh.m_id',
            'machine_id' => 'gh.machine_id',
            'goods_id' => 'gh.g_id',
            'device_created_at' => 'gh.create_time',
            'report_date' => 'gh.create_date',
        ];

        return $fieldMap[$field] ?? $field;
    }

    protected function oldGoodsHitMatchesOnlineCondition($condition)
    {
        $operator = strtolower(strval($condition[1] ?? '='));
        $value = $condition[2] ?? null;

        if ($operator === '=' || $operator === 'eq') {
            return intval($value) === 2;
        }
        if ($operator === '<>' || $operator === '!=' || $operator === 'neq') {
            return intval($value) !== 2;
        }
        if ($operator === 'in') {
            $values = is_array($value) ? $value : explode(',', strval($value));
            return in_array(2, array_map('intval', $values), true);
        }
        if ($operator === 'not in') {
            $values = is_array($value) ? $value : explode(',', strval($value));
            return !in_array(2, array_map('intval', $values), true);
        }

        return true;
    }
}
