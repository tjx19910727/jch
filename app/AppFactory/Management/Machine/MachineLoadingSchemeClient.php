<?php

namespace app\AppFactory\Management\Machine;

use app\AppFactory\Kernel\Service\Machine\LoadingSchemeSnapshotValidator;
use app\AppFactory\Kernel\Traits\Machine\MachineTemplateApiResponseTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class MachineLoadingSchemeClient extends ManagementClient
{
    use MachineTemplateApiResponseTrait;

    const TEMPLATE_ACTIVE = 2;

    public function getGoodsList()
    {
        $postData = input();
        $page = max(1, intval($postData['page'] ?? 1));
        $pageSize = min(200, max(10, intval($postData['page_size'] ?? 100)));
        $keyword = trim((string)($postData['keyword'] ?? ''));
        $placementType = (string)($postData['placement_type'] ?? 'all');
        if (!in_array($placementType, ['all', 'normal', 'hanging'], true)) {
            return $this->templateApiError(400, 'placement_type 只支持 all、normal、hanging');
        }

        try {
            // 商品表没有摆放类型字段。placement_type 仅作为前端布局条件接收，
            // 当前不用于过滤商品，具体摆放类型保存在方案 placement 中。
            $query = Db::name('goods')
                ->where('status', 1)
                ->where('width', '>', 0)
                ->where('height', '>', 0)
                ->where('length', '>', 0)
                ->whereIn('sell_channel', [1, 3]);
            $this->applyGoodsOrganizationScope($query);
            if ($keyword !== '') {
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->whereLike('g_name', '%' . $keyword . '%')
                        ->whereLike('sku', '%' . $keyword . '%', 'OR');
                });
            }

            $total = intval((clone $query)->count());
            $rows = $query
                ->field('g_id,g_name,sku,gc_name,width,height,length')
                ->order('g_id desc')
                ->page($page, $pageSize)
                ->select();

            $list = [];
            foreach ($rows as $goods) {
                $list[] = [
                    'goods_id' => intval($goods['g_id']),
                    'sku' => (string)$goods['sku'],
                    'goods_name' => (string)$goods['g_name'],
                    'category_name' => (string)$goods['gc_name'],
                    'width' => intval($goods['width']),
                    'height' => intval($goods['height']),
                    'depth' => intval($goods['length']),
                    'allow_rotation' => true,
                    'placement_type' => 'normal',
                ];
            }

            return $this->templateApiSuccess([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ]);
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->templateApiError(500, '查询模拟商品失败');
        }
    }

    public function getDetail()
    {
        $templateId = intval(input('template_id'));
        if ($templateId <= 0) {
            return $this->templateApiError(400, 'template_id 参数错误');
        }

        try {
            if (!$this->findTemplate($templateId)) {
                return $this->templateApiError(404, '货道模板不存在');
            }
            $scheme = $this->findSchemeByTemplate($templateId);
            if (!$scheme) {
                return $this->templateApiSuccess(null);
            }
            $snapshot = json_decode((string)$scheme['snapshot_json'], true);
            if (!is_array($snapshot)) {
                return $this->templateApiError(500, '已保存方案快照解析失败');
            }

            return $this->templateApiSuccess([
                'scheme_id' => intval($scheme['scheme_id']),
                'scheme_code' => (string)$scheme['scheme_code'],
                'template_id' => intval($scheme['template_id']),
                'template_version' => intval($scheme['template_version']),
                'scheme_name' => (string)$scheme['scheme_name'],
                'scheme_version' => intval($scheme['scheme_version']),
                'status' => 'saved',
                'saved_at' => intval($scheme['update_time']),
                'summary' => [
                    'total_capacity' => intval($scheme['total_capacity']),
                    'shelf_count' => intval($scheme['shelf_count']),
                    'front_row_count' => intval($scheme['front_row_count']),
                    'sku_count' => intval($scheme['sku_count']),
                    'width_utilization' => round(floatval($scheme['width_utilization']), 2),
                ],
                'snapshot' => $snapshot,
            ]);
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->templateApiError(500, '查询上货方案失败');
        }
    }

    public function save()
    {
        $postData = input();
        $templateId = intval($postData['template_id'] ?? 0);
        $templateVersion = intval($postData['template_version'] ?? 0);
        if ($templateId <= 0 || $templateVersion <= 0) {
            return $this->templateApiError(400, 'template_id 和 template_version 参数错误');
        }

        $snapshot = $postData['snapshot'] ?? null;
        if (is_string($snapshot)) {
            $snapshot = json_decode($snapshot, true);
        }
        if (!is_array($snapshot)) {
            return $this->templateApiError(400, 'snapshot 必须为 JSON 对象');
        }

        $schemeId = intval($postData['scheme_id'] ?? 0);
        $schemeVersion = intval($postData['scheme_version'] ?? 0);
        if (($schemeId > 0 && $schemeVersion <= 0) || ($schemeId <= 0 && $schemeVersion > 0)) {
            return $this->templateApiError(400, '更新方案时 scheme_id 和 scheme_version 必须同时提交');
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
                return $this->templateApiError(409, '模板参数已经变化，请重新进入模拟');
            }

            $validator = new LoadingSchemeSnapshotValidator();
            $validation = $validator->validate($snapshot, $template);
            if (!$validation['valid']) {
                Db::rollback();
                return $this->templateApiError(
                    intval($validation['state'] ?? 422),
                    $validation['message']
                );
            }

            $goodsValidation = $this->validateSelectedGoods($validation['goods_ids']);
            if (!$goodsValidation['valid']) {
                Db::rollback();
                return $this->templateApiError(422, $goodsValidation['message']);
            }

            $currentScheme = $this->findSchemeByTemplate($templateId, true);
            if ($schemeId <= 0 && $currentScheme) {
                Db::rollback();
                return $this->templateApiError(409, '模板已经存在上货方案，请刷新后覆盖保存');
            }
            if ($schemeId > 0) {
                if (!$currentScheme) {
                    Db::rollback();
                    return $this->templateApiError(404, '上货方案不存在');
                }
                if (intval($currentScheme['scheme_id']) !== $schemeId
                    || intval($currentScheme['scheme_version']) !== $schemeVersion) {
                    Db::rollback();
                    return $this->templateApiError(409, '方案已被其他用户修改，请刷新后重试');
                }
            }

            $schemeName = trim((string)($postData['scheme_name'] ?? ''));
            if ($schemeName === '') {
                $schemeName = (string)$template['template_name'] . '上货方案';
            }
            if (mb_strlen($schemeName) > 100) {
                Db::rollback();
                return $this->templateApiError(400, 'scheme_name 最多 100 个字符');
            }

            $summary = $validation['summary'];
            $now = time();
            $managerId = intval($this->manager['manager_id'] ?? 0);
            $saveData = [
                'template_id' => $templateId,
                'ao_id' => intval($template['ao_id']),
                'template_version' => $templateVersion,
                'scheme_name' => $schemeName,
                'schema_version' => 1,
                'snapshot_json' => $validation['snapshot_json'],
                'total_capacity' => intval($summary['total_capacity']),
                'shelf_count' => intval($summary['shelf_count']),
                'front_row_count' => intval($summary['front_row_count']),
                'sku_count' => intval($summary['sku_count']),
                'width_utilization' => round(floatval($summary['width_utilization']), 2),
                'update_id' => $managerId,
                'update_time' => $now,
            ];

            if ($schemeId > 0) {
                $newSchemeVersion = $schemeVersion + 1;
                $saveData['scheme_version'] = $newSchemeVersion;
                $affected = Db::name('machine_loading_scheme')
                    ->where('scheme_id', $schemeId)
                    ->where('template_id', $templateId)
                    ->where('scheme_version', $schemeVersion)
                    ->where('ao_id', intval($template['ao_id']))
                    ->update($saveData);
                if (!$affected) {
                    Db::rollback();
                    return $this->templateApiError(409, '方案版本冲突，请刷新后重试');
                }
                $schemeCode = (string)$currentScheme['scheme_code'];
            } else {
                $newSchemeVersion = 1;
                $schemeCode = $this->generateCode('PLN');
                $saveData += [
                    'scheme_code' => $schemeCode,
                    'scheme_version' => $newSchemeVersion,
                    'creator' => $managerId,
                    'create_time' => $now,
                ];
                $schemeId = intval(Db::name('machine_loading_scheme')->insertGetId($saveData));
            }

            Db::commit();
            return $this->templateApiSuccess([
                'scheme_id' => $schemeId,
                'scheme_code' => $schemeCode,
                'scheme_version' => $newSchemeVersion,
                'template_id' => $templateId,
                'template_version' => $templateVersion,
                'saved_at' => $now,
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            Db::rollback();
            actionException($e, 1);
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return $this->templateApiError(409, '模板已经存在上货方案，请刷新后重试');
            }
            return $this->templateApiError(500, '保存上货方案失败');
        }
    }

    protected function findTemplate($templateId, $lock = false)
    {
        $query = Db::name('machine_channel_template')
            ->where('template_id', intval($templateId))
            ->where('is_del', self::TEMPLATE_ACTIVE);
        $this->applyTemplateOrganizationScope($query);
        if ($lock) {
            $query->lock(true);
        }
        return $query->find();
    }

    protected function findSchemeByTemplate($templateId, $lock = false)
    {
        $query = Db::name('machine_loading_scheme')
            ->where('template_id', intval($templateId));
        $aoId = $this->currentAoId();
        if ($aoId > 1) {
            $query->where('ao_id', $aoId);
        }
        if ($lock) {
            $query->lock(true);
        }
        return $query->find();
    }

    protected function validateSelectedGoods(array $goodsIds)
    {
        $goodsIds = array_values(array_unique(array_map('intval', $goodsIds)));
        if (!$goodsIds) {
            return ['valid' => false, 'message' => '方案未选择商品'];
        }
        $query = Db::name('goods')
            ->whereIn('g_id', $goodsIds)
            ->where('status', 1)
            ->where('width', '>', 0)
            ->where('height', '>', 0)
            ->where('length', '>', 0)
            ->whereIn('sell_channel', [1, 3]);
        $this->applyGoodsOrganizationScope($query);
        $validIds = $query->column('g_id');
        $validIds = array_map('intval', $validIds ?: []);
        $missing = array_values(array_diff($goodsIds, $validIds));
        if ($missing) {
            return [
                'valid' => false,
                'message' => '方案引用了不存在、已禁用、尺寸不完整或无权访问的商品：' . implode(',', $missing),
            ];
        }
        return ['valid' => true, 'message' => ''];
    }

    protected function applyTemplateOrganizationScope($query)
    {
        $aoId = $this->currentAoId();
        if ($aoId > 1) {
            $query->where('ao_id', $aoId);
        }
        return $query;
    }

    protected function applyGoodsOrganizationScope($query)
    {
        $aoId = $this->currentAoId();
        if ($aoId > 1) {
            $query->where('ao_id', $aoId);
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
