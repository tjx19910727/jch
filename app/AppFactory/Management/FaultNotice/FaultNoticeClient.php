<?php

namespace app\AppFactory\Management\FaultNotice;

use app\AppFactory\Kernel\Traits\FaultNotice\FaultDashboardTrait;
use app\AppFactory\Kernel\Traits\FaultNotice\FaultEventTrait;
use app\AppFactory\Kernel\Traits\FaultNotice\FaultSettingTrait;
use app\AppFactory\Kernel\Traits\FaultNotice\FaultCatalogTrait;
use app\AppFactory\Kernel\Traits\FaultNotice\FaultReceiverTrait;
use app\AppFactory\Management\ManagementClient;

/**
 * 故障码重构模块统一Client。
 *
 * 控制器与查询逻辑按功能拆分，整个新模块只注册这一个Client；
 * 不修改、不依赖旧版MachineErrorCodeClient的业务实现。
 */
class FaultNoticeClient extends ManagementClient
{
    use FaultDashboardTrait;
    use FaultEventTrait;
    use FaultSettingTrait;
    use FaultCatalogTrait;
    use FaultReceiverTrait;

    public function getOverview()
    {
        try {
            return $this->r(200, '查询成功', $this->getFaultOverviewData());
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultDashboard');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getTrend($level = 0)
    {
        try {
            return $this->r(200, '查询成功', $this->getFaultTrendData($level));
        } catch (\InvalidArgumentException $e) {
            return $this->rValidate($e->getMessage());
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultDashboard');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getTopRanking($top = 10, $level = 0)
    {
        try {
            return $this->r(200, '查询成功', $this->getFaultTopRankingData($top, $level));
        } catch (\InvalidArgumentException $e) {
            return $this->rValidate($e->getMessage());
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultDashboard');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getMachineTopRanking($top = 10, $level = 0)
    {
        try {
            return $this->r(200, '查询成功', $this->getMachineTopRankingData($top, $level));
        } catch (\InvalidArgumentException $e) {
            return $this->rValidate($e->getMessage());
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultDashboard');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getEventList($params = [], $pageNum = 20)
    {
        try {
            return $this->r(200, '查询成功', $this->getFaultEventList($params, $pageNum));
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultEvent');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getEventDetail($meId)
    {
        try {
            $detail = $this->getFaultEventDetailData($meId);
            if (!$detail) {
                return $this->rNoData();
            }
            return $this->r(200, '查询成功', $detail);
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultEventDetail');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getEventNoticeList($meId, $pageNum = 20)
    {
        try {
            $list = $this->getFaultEventNoticeListData($meId, $pageNum);
            if ($list === null) {
                return $this->rNoData();
            }
            return $this->r(200, '查询成功', $list);
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultEventNoticeList');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function exportFaultEvent($params = [])
    {
        try {
            $list = $this->getFaultEventExportList($params);
            if (!$list) {
                return $this->rNoData();
            }

            $exportList = [];
            foreach ($list as $item) {
                $exportList[] = [
                    'event_id' => $item['event_id'],
                    'create_time_text' => $item['create_time_text'],
                    'machine_id' => $item['machine_id'],
                    'machine_name' => $item['machine_name'],
                    'machine_group_name' => $item['machine_group_name'],
                    'error_code' => $item['error_code'],
                    'error_name' => $item['error_name'],
                    'category_name' => $item['category_name'],
                    'level_name' => $item['level_name'],
                    'event_status_name' => $item['event_status_name'],
                    'notice_status_name' => $item['notice_status_name'],
                    'notice_reason_name' => $item['notice_reason_name'],
                    'notice_receiver_count' => $item['notice_receiver_count'],
                    'notice_success_count' => $item['notice_success_count'],
                    'notice_failed_count' => $item['notice_failed_count'],
                    'notice_time_text' => $item['notice_time_text'],
                    'handle_manager_name' => $item['handle_manager_name'],
                    'handle_time_text' => $item['handle_time_text'],
                ];
            }

            $title = [
                'event_id' => '事件ID',
                'create_time_text' => '发生时间',
                'machine_id' => '设备ID',
                'machine_name' => '设备名称',
                'machine_group_name' => '设备分组',
                'error_code' => '故障码',
                'error_name' => '故障描述',
                'category_name' => '故障分类',
                'level_name' => '故障等级',
                'event_status_name' => '事件状态',
                'notice_status_name' => '通知状态',
                'notice_reason_name' => '未发送/失败原因',
                'notice_receiver_count' => '通知接收人数',
                'notice_success_count' => '发送成功数',
                'notice_failed_count' => '发送失败数',
                'notice_time_text' => '通知时间',
                'handle_manager_name' => '处理人',
                'handle_time_text' => '处理时间',
            ];

            return $this->sendToExport(
                '故障通知-故障事件列表',
                '故障事件-' . date('YmdHis'),
                $title,
                $exportList
            );
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultEventExport');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getGlobalSetting()
    {
        try {
            return $this->r(200, '查询成功', $this->getFaultGlobalSettingData());
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultSettingGlobal');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function saveGlobalSetting($params = [])
    {
        try {
            return $this->r(200, '保存成功', $this->saveFaultGlobalSettingData($params));
        } catch (\InvalidArgumentException $e) {
            return $this->rValidate($e->getMessage());
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultSettingGlobalSave');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getLevelStrategyList()
    {
        try {
            return $this->r(200, '查询成功', $this->getFaultLevelStrategyListData());
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultSettingLevelList');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function addLevelStrategy($params = [])
    {
        try {
            return $this->r(200, '新增成功', $this->addFaultLevelStrategyData($params));
        } catch (\InvalidArgumentException $e) {
            return $this->rValidate($e->getMessage());
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultSettingLevelAdd');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function updateLevelStrategy($params = [])
    {
        try {
            return $this->r(200, '修改成功', $this->updateFaultLevelStrategyData($params));
        } catch (\InvalidArgumentException $e) {
            return $this->rValidate($e->getMessage());
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultSettingLevelUpdate');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function deleteLevelStrategy($level)
    {
        try {
            $data = $this->deleteFaultLevelStrategyData($level);
            return $this->r(200, '删除成功，已恢复系统默认策略', $data);
        } catch (\InvalidArgumentException $e) {
            return $this->rValidate($e->getMessage());
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultSettingLevelDelete');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getFaultSettingOperationLog($params = [], $pageNum = 20)
    {
        try {
            return $this->r(200, '查询成功', $this->getFaultSettingOperationLogData($params, $pageNum));
        } catch (\InvalidArgumentException $e) {
            return $this->rValidate($e->getMessage());
        } catch (\Throwable $e) {
            actionException($e, 1, 'faultSettingOperationLog');
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getCatalogCategoryList()
    {
        return $this->runFaultCatalogAction(function () {
            return $this->r(200, '查询成功', $this->getFaultCatalogCategoryListData());
        }, 'faultCatalogCategoryList');
    }

    public function getCatalogFaultCodeList($params = [], $pageNum = 20)
    {
        return $this->runFaultCatalogAction(function () use ($params, $pageNum) {
            return $this->r(200, '查询成功', $this->getFaultCatalogCodeListData($params, $pageNum));
        }, 'faultCatalogCodeList');
    }

    public function getCatalogFormOptions()
    {
        return $this->runFaultCatalogAction(function () {
            return $this->r(200, '查询成功', $this->getFaultCatalogFormOptionsData());
        }, 'faultCatalogOptions');
    }

    public function addCatalogCategory($params = [])
    {
        return $this->runFaultCatalogAction(function () use ($params) {
            return $this->r(200, '新增成功', $this->addFaultCatalogCategoryData($params));
        }, 'faultCatalogCategoryAdd');
    }

    public function updateCatalogCategory($params = [])
    {
        return $this->runFaultCatalogAction(function () use ($params) {
            return $this->r(200, '修改成功', $this->updateFaultCatalogCategoryData($params));
        }, 'faultCatalogCategoryUpdate');
    }

    public function updateCatalogCategoryStatus($categoryId, $status)
    {
        return $this->runFaultCatalogAction(function () use ($categoryId, $status) {
            return $this->r(200, '操作成功', $this->updateFaultCatalogCategoryStatusData($categoryId, $status));
        }, 'faultCatalogCategoryStatus');
    }

    public function addCatalogFaultCode($params = [])
    {
        return $this->runFaultCatalogAction(function () use ($params) {
            return $this->r(200, '新增成功', $this->addFaultCatalogCodeData($params));
        }, 'faultCatalogCodeAdd');
    }

    public function updateCatalogFaultCode($params = [])
    {
        return $this->runFaultCatalogAction(function () use ($params) {
            return $this->r(200, '修改成功', $this->updateFaultCatalogCodeData($params));
        }, 'faultCatalogCodeUpdate');
    }

    public function updateCatalogFaultCodeStatus($errorCode, $status)
    {
        return $this->runFaultCatalogAction(function () use ($errorCode, $status) {
            return $this->r(200, '操作成功', $this->updateFaultCatalogCodeSwitchData($errorCode, 'status', $status));
        }, 'faultCatalogCodeStatus');
    }

    public function updateCatalogFaultCodeNotice($errorCode, $noticeEnabled)
    {
        return $this->runFaultCatalogAction(function () use ($errorCode, $noticeEnabled) {
            return $this->r(200, '操作成功', $this->updateFaultCatalogCodeSwitchData(
                $errorCode,
                'notice_enabled',
                $noticeEnabled
            ));
        }, 'faultCatalogCodeNotice');
    }

    public function deleteCatalogFaultCode($errorCode)
    {
        return $this->runFaultCatalogAction(function () use ($errorCode) {
            return $this->r(200, '删除成功', $this->deleteFaultCatalogCodeData($errorCode));
        }, 'faultCatalogCodeDelete');
    }

    protected function runFaultCatalogAction($callback, $logName)
    {
        try {
            return $callback();
        } catch (\InvalidArgumentException $e) {
            return $this->rValidate($e->getMessage());
        } catch (\Throwable $e) {
            actionException($e, 1, $logName);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getReceiverList($params = [], $pageNum = 20)
    {
        return $this->runFaultReceiverAction(function () use ($params, $pageNum) {
            return $this->r(200, '查询成功', $this->getFaultReceiverListData($params, $pageNum));
        }, 'faultReceiverList');
    }

    public function getReceiverDetail($receiverId)
    {
        return $this->runFaultReceiverAction(function () use ($receiverId) {
            $data = $this->getFaultReceiverDetailData($receiverId);
            if (!$data) {
                return $this->rNoData();
            }
            return $this->r(200, '查询成功', $data);
        }, 'faultReceiverDetail');
    }

    public function addReceiver($params = [])
    {
        return $this->runFaultReceiverAction(function () use ($params) {
            return $this->r(200, '新增成功', $this->addFaultReceiverData($params));
        }, 'faultReceiverAdd');
    }

    public function updateReceiver($params = [])
    {
        return $this->runFaultReceiverAction(function () use ($params) {
            return $this->r(200, '修改成功', $this->updateFaultReceiverData($params));
        }, 'faultReceiverUpdate');
    }

    public function updateReceiverStatus($receiverId, $status)
    {
        return $this->runFaultReceiverAction(function () use ($receiverId, $status) {
            return $this->r(200, '操作成功', $this->updateFaultReceiverStatusData($receiverId, $status));
        }, 'faultReceiverStatus');
    }

    public function deleteReceiver($receiverId)
    {
        return $this->runFaultReceiverAction(function () use ($receiverId) {
            return $this->r(200, '删除成功', $this->deleteFaultReceiverData($receiverId));
        }, 'faultReceiverDelete');
    }

    protected function runFaultReceiverAction($callback, $logName)
    {
        try {
            return $callback();
        } catch (\InvalidArgumentException $e) {
            return $this->rValidate($e->getMessage());
        } catch (\Throwable $e) {
            actionException($e, 1, $logName);
            return $this->rTryCatch($e->getMessage());
        }
    }
}
