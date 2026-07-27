<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/7/1
 * Time: 11:44
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Management\ManagementClient;
use app\AppFactory\Kernel\Traits\Machine\MachineLayoutModelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineLayoutDetailTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineLevelDescTrait;

class MachineLayoutModelClient extends ManagementClient
{
    use MachineLayoutModelTrait, MachineLayoutDetailTrait, MachineLevelDescTrait;

    /**
     * 获取列表（兼容父类 ManagementTrait::getList 签名）
     * @param array $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param int $rQ
     * @return array|string
     */
    public function getList($where = [], $pageNum = 0, $field = "*", $order = "mlm_id desc", $rQ = 1)
    {
        if (!$where && $field == "*") {
            $field = "mlm_id,model_name,machine_level,inner_width,inner_height,inner_depth,shelf_thickness,divider_thickness,left_indent,right_indent,channel_width,custom_channel_widths,channel_height,channel_depth,total_rows,total_cols,actual_channel_width,status,create_time,update_time";
        }
        return $this->rQ($this->getMachineLayoutModelList($where, $pageNum, $field, $order));
    }

    /**
     * 添加或编辑
     */
    public function save()
    {
        $postData = input();
        $mlmId = intval($postData['mlm_id'] ?? 0);
        $machineLevel = intval($postData['machine_level'] ?? 0);
        if ($machineLevel <= 0) {
            return $this->rFail("设备等级不能为空");
        }
        if (!$this->getMachineLevelFind(['machine_level' => $machineLevel], 'machine_level')) {
            return $this->rFail("设备等级不存在");
        }
        $customWidthsError = '';
        $customChannelWidths = $this->normalizeCustomChannelWidthsForSave(
            $postData['custom_channel_widths'] ?? ($postData['channel_widths'] ?? []),
            $customWidthsError
        );
        if ($customWidthsError) {
            return $this->rFail($customWidthsError);
        }

        $insert = [
            "model_name" => $postData['model_name'] ?? '',
            "machine_level" => $machineLevel,
            "inner_width" => floatval($postData['inner_width'] ?? 0),
            "inner_height" => floatval($postData['inner_height'] ?? 0),
            "inner_depth" => floatval($postData['inner_depth'] ?? 0),
            "shelf_thickness" => floatval($postData['shelf_thickness'] ?? 0),
            "divider_thickness" => floatval($postData['divider_thickness'] ?? 0),
            "left_indent" => floatval($postData['left_indent'] ?? 0),
            "right_indent" => floatval($postData['right_indent'] ?? 0),
            "channel_width" => floatval($postData['channel_width'] ?? 0),
            "custom_channel_widths" => $customChannelWidths,
            "channel_height" => floatval($postData['channel_height'] ?? 0),
            "channel_depth" => floatval($postData['channel_depth'] ?? 0),
        ];
        if (isset($postData['total_cols'])) {
            $insert['total_cols'] = intval($postData['total_cols']);
        }

        $this->startTrans();
        try {
            if ($mlmId) {
                $this->updateMachineLayoutModel($insert, ['mlm_id' => $mlmId]);
            } else {
                $mlmId = $this->addMachineLayoutModel($insert);
            }

            // 重新生成布局明细
            $result = $this->generateLayoutDetail($mlmId);
            if ($result !== true) {
                $this->rollbackTrans();
                return $result;
            }

            $this->commitTrans();
            return $this->rSuccess();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rFail($e->getMessage());
        }
    }

    /**
     * 获取详情
     */
    public function getDetail()
    {
        $mlmId = intval(input('mlm_id'));
        if (!$mlmId) return $this->rFail("参数错误");

        $model = $this->getMachineLayoutModelFind(['mlm_id' => $mlmId]);
        if (!$model) return $this->rFail("数据不存在");

        $model = $model->toArray();
        $model['details'] = $this->getMachineLayoutDetailList(['mlm_id' => $mlmId])->toArray();
        return $this->r(200, "查询成功", $model);
    }

    /**
     * 删除
     */
    public function del($where = [], $rD = 1)
    {
        $mlmId = intval(input('mlm_id'));
        if (!$mlmId) return $this->rFail("参数错误");

        $this->startTrans();
        try {
            $this->delMachineLayoutDetail(['mlm_id' => $mlmId]);
            $this->delMachineLayoutModel(['mlm_id' => $mlmId]);
            $this->commitTrans();
            return $this->rSuccess();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rFail($e->getMessage());
        }
    }

    /**
     * 重新计算布局
     */
    public function recalc()
    {
        $mlmId = intval(input('mlm_id'));
        if (!$mlmId) return $this->rFail("参数错误");

        $result = $this->generateLayoutDetail($mlmId);
        if ($result !== true) return $result;

        return $this->rSuccess();
    }

    // ==================== 设备等级关联管理 ====================

    /**
     * 获取设备等级关联的布局模板列表
     * @return array|string
     */
    public function getLevelLayoutRel()
    {
        $machineLevel = intval(input('machine_level'));
        if (!$machineLevel) return $this->rFail("参数错误");

        $modelList = $this->getMachineLayoutModelList(
            ['machine_level' => $machineLevel],
            0,
            "mlm_id,model_name,machine_level"
        );
        $relList = [];
        foreach ($modelList as $model) {
            $relList[] = [
                'machine_level' => $machineLevel,
                'mlm_id' => intval($model['mlm_id']),
            ];
        }

        return $this->r(200, "查询成功", [
            'rel_list' => $relList,
            'model_list' => $modelList,
        ]);
    }

    /**
     * 保存设备等级与布局模板的关联（全量替换）
     * @return array|string
     */
    public function saveLevelLayoutRel()
    {
        $postData = input();
        $machineLevel = intval($postData['machine_level'] ?? 0);
        $mlmIds = $postData['mlm_ids'] ?? [];

        if (!$machineLevel) return $this->rFail("设备等级参数错误");
        if (!is_array($mlmIds)) return $this->rFail("布局模板ID参数错误");

        if (!$this->getMachineLevelFind(['machine_level' => $machineLevel], 'machine_level')) {
            return $this->rFail("设备等级不存在");
        }

        $normalizedMlmIds = [];
        foreach ($mlmIds as $mlmId) {
            $mlmId = intval($mlmId);
            if ($mlmId > 0) {
                $normalizedMlmIds[$mlmId] = $mlmId;
            }
        }

        $this->startTrans();
        try {
            $this->updateMachineLayoutModel(
                ['machine_level' => 0],
                ['machine_level' => $machineLevel]
            );
            if ($normalizedMlmIds) {
                $this->updateMachineLayoutModel(
                    ['machine_level' => $machineLevel],
                    [['mlm_id', 'in', array_values($normalizedMlmIds)]]
                );
            }
            $this->commitTrans();
            return $this->rSuccess();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rFail($e->getMessage());
        }
    }

    // ==================== 布局计算核心逻辑 ====================

    /**
     * 根据布局模板配置，重新生成 layout_detail
     * @param int $mlmId
     * @return bool
     */
    protected function generateLayoutDetail($mlmId)
    {
        $model = $this->getMachineLayoutModelFind(['mlm_id' => $mlmId]);
        if (!$model) return $this->rFail("布局模板不存在");
        $model = $model->toArray();

        // 读取参数
        $innerWidth = floatval($model['inner_width']);
        $innerHeight = floatval($model['inner_height']);
        $shelfThickness = floatval($model['shelf_thickness']);
        $dividerThickness = floatval($model['divider_thickness']);
        $leftIndent = floatval($model['left_indent']);
        $rightIndent = floatval($model['right_indent']);
        $channelWidth = floatval($model['channel_width']);
        $channelHeight = floatval($model['channel_height']);

        if ($innerWidth <= 0 || $innerHeight <= 0 || $channelWidth <= 0 || $channelHeight <= 0) {
            return $this->rFail("参数不完整，无法计算布局");
        }

        // Step1: 可用宽度
        $availableWidth = $innerWidth - $leftIndent - $rightIndent;
        if ($availableWidth <= 0) return $this->rFail("可用宽度不足");

        // Step2: 计算层数
        $unitHeight = $channelHeight + $shelfThickness;
        $totalRows = intval(floor($innerHeight / $unitHeight));
        if ($totalRows <= 0) return $this->rFail("内部高度不足以容纳任何货道");

        // Step3: 计算列数。传入 total_cols 时按固定货道数量计算，否则兼容旧逻辑自动推算。
        $totalCols = intval($model['total_cols'] ?? 0);
        if ($totalCols <= 0) {
            $totalCols = intval(floor(($availableWidth + $dividerThickness) / ($channelWidth + $dividerThickness)));
        }
        if ($totalCols <= 0) return $this->rFail("可用宽度不足以容纳任何货道");

        // Step4: 计算每列货道宽度。指定列使用自定义宽度，未指定列均分剩余空间。
        $widthResult = $this->calculateChannelWidths(
            $totalCols,
            $availableWidth,
            $dividerThickness,
            $channelWidth,
            $model['custom_channel_widths'] ?? ''
        );
        if ($widthResult['error']) {
            return $this->rFail($widthResult['error']);
        }
        $channelWidths = $widthResult['widths'];
        $actualChannelWidth = $widthResult['average_width'];

        // Step5: 更新主表自动计算字段
        $this->updateMachineLayoutModel([
            'total_rows' => $totalRows,
            'total_cols' => $totalCols,
            'actual_channel_width' => round($actualChannelWidth, 2),
        ], ['mlm_id' => $mlmId]);

        // Step6: 清空旧的明细并重新生成
        $this->delMachineLayoutDetail(['mlm_id' => $mlmId]);

        $insertAll = [];
        $now = time();
        for ($row = 0; $row < $totalRows; $row++) {
            $posY = $row * ($channelHeight + $shelfThickness);
            $posX = $leftIndent;
            for ($col = 0; $col < $totalCols; $col++) {
                $rowChar = chr(65 + $row);
                $colStr = str_pad($col + 1, 2, '0', STR_PAD_LEFT);
                $channelCode = $rowChar . $colStr;
                $currentWidth = $channelWidths[$col + 1];

                $insertAll[] = [
                    'mlm_id' => $mlmId,
                    'row_index' => $row + 1,
                    'col_index' => $col + 1,
                    'channel_code' => $channelCode,
                    'pos_x' => round($posX, 2),
                    'pos_y' => round($posY, 2),
                    'actual_width' => round($currentWidth, 2),
                    'actual_height' => $channelHeight,
                    'create_time' => $now,
                ];
                $posX += $currentWidth + $dividerThickness;
            }
        }

        if ($insertAll) {
            $this->addMachineLayoutDetailAll($insertAll);
        }

        return true;
    }

    protected function normalizeCustomChannelWidthsForSave($customWidths, &$error = '')
    {
        $widthMap = $this->parseCustomChannelWidths($customWidths, $error);
        if ($error) {
            return '';
        }
        if (empty($widthMap)) {
            return '';
        }

        $items = [];
        ksort($widthMap);
        foreach ($widthMap as $colIndex => $width) {
            $items[] = [
                'col_index' => intval($colIndex),
                'width' => round(floatval($width), 2),
            ];
        }
        return json_encode($items, JSON_UNESCAPED_UNICODE);
    }

    protected function calculateChannelWidths($totalCols, $availableWidth, $dividerThickness, $defaultChannelWidth, $customWidths)
    {
        $result = [
            'widths' => [],
            'average_width' => 0,
            'error' => '',
        ];

        $totalCols = intval($totalCols);
        if ($totalCols <= 0) {
            $result['error'] = '货道数量必须大于0';
            return $result;
        }

        if ($dividerThickness < 0) {
            $result['error'] = '隔板宽度不能小于0';
            return $result;
        }

        $dividerTotalWidth = $dividerThickness * max(0, $totalCols - 1);
        $availableChannelWidth = $availableWidth - $dividerTotalWidth;
        if ($availableChannelWidth <= 0) {
            $result['error'] = '可用宽度不足，无法扣除隔板宽度';
            return $result;
        }

        $parseError = '';
        $widthMap = $this->parseCustomChannelWidths($customWidths, $parseError);
        if ($parseError) {
            $result['error'] = $parseError;
            return $result;
        }
        $customTotalWidth = 0;
        $customCount = 0;
        foreach ($widthMap as $colIndex => $width) {
            if ($colIndex < 1 || $colIndex > $totalCols) {
                $result['error'] = '自定义货道列号超出货道数量范围';
                return $result;
            }
            if ($width <= 0) {
                $result['error'] = '自定义货道宽度必须大于0';
                return $result;
            }
            $customTotalWidth += $width;
            $customCount++;
        }

        if ($customTotalWidth > $availableChannelWidth) {
            $result['error'] = '自定义货道宽度总和超过可用宽度';
            return $result;
        }

        $remainCount = $totalCols - $customCount;
        if ($remainCount > 0) {
            $remainWidth = ($availableChannelWidth - $customTotalWidth) / $remainCount;
        } else {
            $remainWidth = 0;
            if (abs($availableChannelWidth - $customTotalWidth) > 0.01) {
                $result['error'] = '全部货道已指定宽度时，货道宽度总和必须等于可用宽度';
                return $result;
            }
        }

        if ($remainCount > 0 && $remainWidth <= 0) {
            $result['error'] = '剩余货道均分宽度必须大于0';
            return $result;
        }

        for ($col = 1; $col <= $totalCols; $col++) {
            $result['widths'][$col] = isset($widthMap[$col]) ? $widthMap[$col] : $remainWidth;
        }

        $result['average_width'] = round(array_sum($result['widths']) / $totalCols, 2);
        return $result;
    }

    protected function parseCustomChannelWidths($customWidths, &$error = '')
    {
        if (empty($customWidths)) {
            return [];
        }

        if (is_string($customWidths)) {
            $decoded = json_decode($customWidths, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                $error = '自定义货道宽度JSON格式错误';
                return [];
            }
            $customWidths = $decoded;
        }

        if (!is_array($customWidths)) {
            $error = '自定义货道宽度参数格式错误';
            return [];
        }

        $widthMap = [];
        foreach ($customWidths as $key => $item) {
            if (is_array($item)) {
                $colIndex = intval($item['col_index'] ?? ($item['col'] ?? 0));
                $width = floatval($item['width'] ?? 0);
            } else {
                $colIndex = is_numeric($key) ? intval($key) : 0;
                $width = floatval($item);
            }

            if ($colIndex <= 0) {
                $error = '自定义货道列号必须大于0';
                return [];
            }
            if (isset($widthMap[$colIndex])) {
                $error = '自定义货道列号不能重复';
                return [];
            }
            $widthMap[$colIndex] = $width;
        }

        return $widthMap;
    }
}
