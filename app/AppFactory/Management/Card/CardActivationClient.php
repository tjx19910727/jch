<?php
/**
 * 卡激活活动业务
 */

namespace app\AppFactory\Management\Card;

use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Card\CardActivationTrait;
use app\AppFactory\Kernel\Traits\Card\CardTrait;
use app\AppFactory\Management\ManagementClient;

class CardActivationClient extends ManagementClient
{
    use CardTrait;
    use CardActivationTrait;

    protected $activationActiveStatuses = [1, 2];

    public function getCardActivationInfoList($where, $pageNum = 0, $field = "*", $order = "id desc")
    {
        $field = 'id,pick_name,money,`desc`,start_time,end_time,status,create_time,update_time,
            (SELECT count(acd_id) FROM activity_card_activation_detail d WHERE d.aca_id = a.id) total_num,
            (SELECT count(acd_id) FROM activity_card_activation_detail d WHERE d.aca_id = a.id AND d.status = 2) used_num';
        return $this->rQ($this->getActivityCardActivationList($where, $pageNum, $field, $order));
    }

    public function getCardActivationInfoFind($where)
    {
        $activity = $this->getActivityCardActivationFind($where);
        if (!$activity) {
            return $this->rNoData();
        }
        $activity = $activity->toArray();
        $detail = $this->getActivityCardActivationDetailList(['aca_id' => $activity['id']], 0, 'acd_id,card_no,status,used_time', 'acd_id desc');
        $activity['cardList'] = $detail ? $detail->toArray() : [];
        return $this->rQ($activity);
    }

    public function addCardActivationInfo($postData)
    {
        $this->startTrans();
        try {
            $insert = $postData;
            unset($insert['card_no_list'], $insert['card_no'], $insert['cardList']);
            $insert['status'] = $this->getActivityStatusByTime($insert);
            $insert['create_time'] = time();
            $insert['update_time'] = time();
            $acaId = $this->addActivityCardActivation($insert);
            if (!$acaId) {
                $this->rollbackTrans();
                return $this->rFail('添加活动失败');
            }

            $cardNos = $this->normalizeCardNos($postData);
            if ($cardNos) {
                $check = $this->buildInsertableActivationCards($acaId, $cardNos, true);
                if (!$check['ok']) {
                    $this->rollbackTrans();
                    return $this->r(100, $check['msg'], $check['data']);
                }
                if ($check['insert']) {
                    $this->addActivityCardActivationDetailMore($check['insert']);
                }
            }

            $this->commitTrans();
            return $this->rAction(true);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
    }

    public function updateCardActivationInfo($postData)
    {
        $acaId = intval($postData['id'] ?? 0);
        if ($acaId <= 0) {
            return $this->rValidate('活动ID错误');
        }
        $activity = $this->getActivityCardActivationFind(['id' => $acaId]);
        if (!$activity) {
            return $this->rNoData();
        }

        $this->startTrans();
        try {
            $update = $postData;
            unset($update['card_no_list'], $update['card_no'], $update['cardList']);
            $update['status'] = $this->getActivityStatusByTime($update + $activity->toArray());
            $update['update_time'] = time();
            $this->updateActivityCardActivation($update, ['id' => $acaId]);

            $selectedCardNos = $this->normalizeCardNos($postData);
            if ($selectedCardNos) {
                $detailList = $this->getActivityCardActivationDetailList(['aca_id' => $acaId], 0, 'acd_id,card_no,status');
                $detailList = $detailList ? $detailList->toArray() : [];
                $existingCardNos = array_column($detailList, 'card_no');
                $usedCardNos = [];
                $unusedAcdMap = [];
                foreach ($detailList as $row) {
                    if (intval($row['status']) === 2) {
                        $usedCardNos[] = $row['card_no'];
                    } else {
                        $unusedAcdMap[$row['card_no']] = $row['acd_id'];
                    }
                }

                $toDeleteUnused = array_diff(array_keys($unusedAcdMap), $selectedCardNos);
                if ($toDeleteUnused) {
                    $acdIds = [];
                    foreach ($toDeleteUnused as $cardNo) {
                        $acdIds[] = $unusedAcdMap[$cardNo];
                    }
                    $this->delActivityCardActivationDetail([['acd_id', 'in', $acdIds], ['status', '=', 1]]);
                }

                $toAdd = array_diff($selectedCardNos, $existingCardNos);
                if ($toAdd) {
                    $check = $this->buildInsertableActivationCards($acaId, $toAdd, true);
                    if (!$check['ok']) {
                        $this->rollbackTrans();
                        return $this->r(100, $check['msg'], $check['data']);
                    }
                    if ($check['insert']) {
                        $this->addActivityCardActivationDetailMore($check['insert']);
                    }
                }

                if ($usedCardNos) {
                    $postData['locked_used_card_nos'] = $usedCardNos;
                }
            }

            $this->commitTrans();
            return $this->rAction(true);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
    }

    public function delCardActivationInfo($id)
    {
        $acaId = intval($id);
        if ($acaId <= 0) {
            return $this->rValidate('活动ID错误');
        }
        $activity = $this->getActivityCardActivationFind(['id' => $acaId]);
        if (!$activity) {
            return $this->rNoData();
        }
        $usedCount = $this->getActivityCardActivationDetailCount(['aca_id' => $acaId, 'status' => 2]);
        if ($usedCount > 0) {
            return $this->r(100, '活动存在已使用记录，不可删除');
        }
        $this->startTrans();
        try {
            $this->delActivityCardActivationDetail(['aca_id' => $acaId]);
            $result = $this->delActivityCardActivation(['id' => $acaId]);
            $this->commitTrans();
            return $this->rD($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rValidate($e->getMessage());
        }
    }

    public function getCardActivationDetailInfoList($where, $pageNum = 0, $field = "*", $order = "acd_id desc")
    {
        $field = 'acd_id,aca_id,money,trade_no,card_no,balance_log_id,status,create_time,used_time';
        return $this->rQ($this->getActivityCardActivationDetailList($where, $pageNum, $field, $order));
    }

    public function delCardActivationDetailInfo($acdId)
    {
        $detail = $this->getActivityCardActivationDetailFind(['acd_id' => intval($acdId)]);
        if (!$detail) {
            return $this->rNoData();
        }
        if (intval($detail['status']) === 2) {
            return $this->r(100, '已使用记录不可删除');
        }
        return $this->rD($this->delActivityCardActivationDetail(['acd_id' => intval($acdId), 'status' => 1]));
    }

    public function importCardActivationDetailInfo($postData)
    {
        $acaId = intval($postData['id'] ?? 0);
        if ($acaId <= 0) {
            return $this->rValidate('活动ID错误');
        }
        $activity = $this->getActivityCardActivationFind(['id' => $acaId]);
        if (!$activity) {
            return $this->rNoData();
        }
        $path = root_path() . 'public' . ($postData['file_path'] ?? '');
        $rows = Excel::importExcel($path, ['card_no']);
        if (!$rows) {
            return $this->r(100, '导入数据为空');
        }

        $cardNos = [];
        foreach ($rows as $row) {
            $cardNo = trim((string)($row['card_no'] ?? ''));
            if ($cardNo !== '') {
                $cardNos[] = $cardNo;
            }
        }
        $cardNos = array_values(array_unique($cardNos));
        if (!$cardNos) {
            return $this->r(100, '导入卡号为空');
        }

        $check = $this->buildInsertableActivationCards($acaId, $cardNos, false);
        if ($check['insert']) {
            $this->addActivityCardActivationDetailMore($check['insert']);
        }

        $data = [
            'total' => count($cardNos),
            'imported' => count($check['insert']),
            'filtered' => count($cardNos) - count($check['insert']),
            'filtered_detail' => $check['filtered'],
        ];
        return $this->r(200, '导入完成', $data);
    }

    public function getSelectableUnactivatedCards($postData, $pageNum = 0)
    {
        $where = [['status', '=', 0]];
        if (!empty($postData['card_no'])) {
            $where[] = ['card_no', 'like', '%' . $postData['card_no'] . '%'];
        }
        if (!empty($postData['card_show_no'])) {
            $where[] = ['card_show_no', 'like', '%' . $postData['card_show_no'] . '%'];
        }
        if (!empty($postData['name'])) {
            $where[] = ['name', 'like', '%' . $postData['name'] . '%'];
        }
        return $this->rQ($this->getCardList($where, $pageNum, 'card_no,card_show_no,name,status', 'card_no desc'));
    }

    protected function normalizeCardNos($postData)
    {
        if (!isset($postData['card_no_list'])) {
            return [];
        }

        $cardNos = [];
        $list = explode(',', trim((string)$postData['card_no_list']));
        foreach ($list as $item) {
            $cardNo = trim((string)$item);
            if ($cardNo !== '') {
                $cardNos[] = $cardNo;
            }
        }

        return array_values(array_unique($cardNos));
    }

    protected function buildInsertableActivationCards($acaId, $cardNos, $errorOnConflict = true)
    {
        $now = time();
        $activity = $this->getActivityCardActivationFind(['id' => $acaId], 'id,money,status');
        if (!$activity) {
            return ['ok' => false, 'msg' => '活动不存在', 'data' => []];
        }
        $money = floatval($activity['money'] ?? 0);

        $existsInCurrent = $this->getActivityCardActivationDetailColumn([
            'aca_id' => $acaId,
            ['card_no', 'in', $cardNos]
        ], 'card_no');
        $existsInCurrent = $existsInCurrent ? array_values(array_unique($existsInCurrent)) : [];

        $cardRows = $this->getCardList([['card_no', 'in', $cardNos]], 0, 'card_no,status');
        $cardRows = $cardRows ? $cardRows->toArray() : [];
        $cardMap = [];
        foreach ($cardRows as $row) {
            $cardMap[$row['card_no']] = intval($row['status']);
        }

        $detailRows = $this->getActivityCardActivationDetailList([
            ['card_no', 'in', $cardNos],
            ['status', '=', 1],
            ['aca_id', '<>', $acaId]
        ], 0, 'aca_id,card_no');
        $detailRows = $detailRows ? $detailRows->toArray() : [];
        $otherAcaIds = array_values(array_unique(array_column($detailRows, 'aca_id')));
        $activeAcaIds = [];
        if ($otherAcaIds) {
            $activeAcaIds = $this->getActivityCardActivationColumn([
                ['id', 'in', $otherAcaIds],
                ['status', 'in', $this->activationActiveStatuses]
            ], 'id');
            $activeAcaIds = $activeAcaIds ? array_values(array_unique($activeAcaIds)) : [];
        }
        $conflictCardNos = [];
        if ($activeAcaIds) {
            foreach ($detailRows as $row) {
                if (in_array($row['aca_id'], $activeAcaIds)) {
                    $conflictCardNos[] = $row['card_no'];
                }
            }
            $conflictCardNos = array_values(array_unique($conflictCardNos));
        }

        if ($errorOnConflict && $conflictCardNos) {
            return [
                'ok' => false,
                'msg' => '部分卡号已被其他活动占用',
                'data' => ['card_no_list' => $conflictCardNos]
            ];
        }

        $insert = [];
        $filtered = [
            'not_exist' => [],
            'already_activated' => [],
            'already_in_activity' => $existsInCurrent,
            'occupied_by_other_activity' => $conflictCardNos,
        ];

        foreach ($cardNos as $cardNo) {
            if (in_array($cardNo, $existsInCurrent)) {
                continue;
            }
            if (in_array($cardNo, $conflictCardNos)) {
                continue;
            }
            if (!array_key_exists($cardNo, $cardMap)) {
                $filtered['not_exist'][] = $cardNo;
                continue;
            }
            if (intval($cardMap[$cardNo]) === 1) {
                $filtered['already_activated'][] = $cardNo;
                continue;
            }
            $insert[] = [
                'aca_id' => $acaId,
                'money' => $money,
                'card_no' => $cardNo,
                'status' => 1,
                'create_time' => $now,
            ];
        }

        return [
            'ok' => true,
            'msg' => 'ok',
            'insert' => $insert,
            'filtered' => $filtered,
            'data' => []
        ];
    }

    protected function getActivityStatusByTime($data)
    {
        $now = time();
        $startTime = intval($data['start_time'] ?? 0);
        $endTime = intval($data['end_time'] ?? 0);
        if ($endTime > 0 && $endTime < $now) {
            return 3;
        }
        if ($startTime > 0 && $startTime <= $now) {
            return 2;
        }
        return 1;
    }
}
