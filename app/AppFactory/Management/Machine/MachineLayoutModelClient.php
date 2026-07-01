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
use app\AppFactory\Kernel\Traits\Machine\MachineLevelLayoutRelTrait;

class MachineLayoutModelClient extends ManagementClient
{
    use MachineLayoutModelTrait, MachineLayoutDetailTrait, MachineLevelLayoutRelTrait;

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
            $field = "mlm_id,model_name,model_code,inner_width,inner_height,inner_depth,shelf_thickness,divider_thickness,left_indent,right_indent,channel_width,channel_height,channel_depth,total_rows,total_cols,actual_channel_width,status,create_time,update_time";
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

        $insert = [
            "model_name" => $postData['model_name'] ?? '',
            "model_code" => $postData['model_code'] ?? '',
            "inner_width" => floatval($postData['inner_width'] ?? 0),
            "inner_height" => floatval($postData['inner_height'] ?? 0),
            "inner_depth" => floatval($postData['inner_depth'] ?? 0),
            "shelf_thickness" => floatval($postData['shelf_thickness'] ?? 0),
            "divider_thickness" => floatval($postData['divider_thickness'] ?? 0),
            "left_indent" => floatval($postData['left_indent'] ?? 0),
            "right_indent" => floatval($postData['right_indent'] ?? 0),
            "channel_width" => floatval($postData['channel_width'] ?? 0),
            "channel_height" => floatval($postData['channel_height'] ?? 0),
            "channel_depth" => floatval($postData['channel_depth'] ?? 0),
        ];

        if ($mlmId) {
            $this->updateMachineLayoutModel($insert, ['mlm_id' => $mlmId]);
        } else {
            $mlmId = $this->addMachineLayoutModel($insert);
        }

        // 重新生成布局明细
        $this->generateLayoutDetail($mlmId);

        return $this->rSuccess();
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

        $relList = $this->getMachineLevelLayoutRelList(['machine_level' => $machineLevel]);
        $mlmIds = [];
        foreach ($relList as $rel) {
            $mlmIds[] = $rel['mlm_id'];
        }

        // 查询布局模板列表
        $modelList = [];
        if (!empty($mlmIds)) {
            $modelList = $this->getMachineLayoutModelList([['mlm_id', 'in', $mlmIds]], 0, "mlm_id,model_name,model_code");
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

        $this->_saveLevelLayoutRel($machineLevel, $mlmIds);

        return $this->rSuccess();
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

        // Step3: 计算列数
        $totalCols = intval(floor(($availableWidth + $dividerThickness) / ($channelWidth + $dividerThickness)));
        if ($totalCols <= 0) return $this->rFail("可用宽度不足以容纳任何货道");

        // Step4: 剩余宽度均匀分配到每个货道
        $usedWidth = $totalCols * $channelWidth + ($totalCols - 1) * $dividerThickness;
        $remainingWidth = $availableWidth - $usedWidth;
        $actualChannelWidth = $channelWidth;
        if ($totalCols > 0) {
            $actualChannelWidth = $channelWidth + ($remainingWidth / $totalCols);
        }

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
            for ($col = 0; $col < $totalCols; $col++) {
                $posX = $leftIndent + $col * ($actualChannelWidth + $dividerThickness);
                $rowChar = chr(65 + $row);
                $colStr = str_pad($col + 1, 2, '0', STR_PAD_LEFT);
                $channelCode = $rowChar . $colStr;

                $insertAll[] = [
                    'mlm_id' => $mlmId,
                    'row_index' => $row + 1,
                    'col_index' => $col + 1,
                    'channel_code' => $channelCode,
                    'pos_x' => round($posX, 2),
                    'pos_y' => round($posY, 2),
                    'actual_width' => round($actualChannelWidth, 2),
                    'actual_height' => $channelHeight,
                    'create_time' => $now,
                ];
            }
        }

        if ($insertAll) {
            $this->addMachineLayoutDetailAll($insertAll);
        }

        return true;
    }
}