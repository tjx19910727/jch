<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/4/10
 * Time: 11:42
 */

namespace app\AppFactory\Management\Template;


use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Template\TopicPageTrait;
use app\AppFactory\Management\ManagementClient;

class TopicPageClient extends ManagementClient
{
    use TopicPageTrait;
    use MachineTrait;

    public function getTopicList($where, $pageNum = 0, $field = "*", $order = "id desc")
    {
        $list = $this->getTopicPageList($where, $pageNum, $field, $order);
        if ($list) {
            $topicIds = [];
            foreach ($list as $item) {
                $topicIds[] = intval($item['id']);
            }
            $topicIds = array_values(array_unique(array_filter($topicIds)));
            //先获得所有主题下的机器绑定数据，再进行组装，避免循环内查询数据库（N+1问题）
            $payloadMap = $this->buildTopicMachinePayloadMap($topicIds);
            if ($pageNum) {
                $list->each(function ($item) use ($payloadMap) {
                    $payload = $payloadMap[intval($item['id'])] ?? $this->emptyMachinePayload();
                    return $this->appendMachinePayload($item, $payload);
                });
            } else {
                $list = $list->toArray();
                foreach ($list as $key => $item) {
                    $payload = $payloadMap[intval($item['id'])] ?? $this->emptyMachinePayload();
                    $list[$key] = $this->appendMachinePayload($item, $payload);
                }
            }
        }
        return $this->rQ($list);
    }

    public function getTopicFindData($where, $field = "*")
    {
        $item = $this->getTopicPageFind($where, $field);
        if ($item) {
            $item = $this->appendMachineListToItem($item->toArray());
            $item = $this->appendTopicGroupPayload($item);
        }
        return $this->rQ($item);
    }

    public function updateTopic($postData)
    {
        $topic = $this->getCurrentTopic($postData['id'] ?? 0);
        if (!$topic) return $this->rFail(lang('VTopicPage.data_empty'));

        $result = $this->updateTopicPage($postData);
        if ($result && $this->getTopicPageMachineCount(['topic_id' => $topic['id']]) > 0) {
            $this->sendTopicUpdateMq($topic['id']);
        }
        return $this->rU($result);
    }

    public function delTopic($postData)
    {
        $topic = $this->getCurrentTopic($postData['id'] ?? 0);
        if (!$topic) return $this->rFail(lang('VTopicPage.data_empty'));

        $bindCount = $this->getTopicPageMachineCount(['topic_id' => $topic['id']]);
        if ($bindCount > 0) return $this->rFail(lang('VTopicPage.data_assigned'));

        return $this->rD($this->delTopicPage(['id' => $topic['id']]));
    }

    public function assignMachine($postData)
    {
        $topic = $this->getCurrentTopic($postData['id'] ?? 0);
        if (!$topic) return $this->rFail(lang('VTopicPage.data_empty'));

        $mIds = $this->parseMIds($postData['m_ids'] ?? '');
        $insertAll = [];
        if ($mIds) {
            $machineList = $this->getMachineList([['m_id', 'in', $mIds]], 0, 'm_id,machine_id');
            if (!$machineList || count($machineList) != count($mIds)) {
                return $this->rFail(lang('VTopicPage.data_invalid'));
            }
            foreach ($machineList as $item) {
                $insertAll[] = [
                    'topic_id' => $topic['id'],
                    'm_id' => $item['m_id'],
                    'machine_id' => $item['machine_id'],
                ];
            }
        }

        $this->startTrans();
        try {
            $this->delTopicPageMachine(['topic_id' => $topic['id']]);
            if (!empty($insertAll)) {
                $result = $this->addTopicPageMachineMore($insertAll);
                if ($result === false) {
                    $this->rollbackTrans();
                    return $this->rFail(lang('VTopicPage.data_assign_fail'));
                }
            }
            $this->commitTrans();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }

        $this->sendTopicUpdateMq($topic['id']);
        return $this->rAction(true);
    }

    public function setStatus($postData)
    {
        $topic = $this->getCurrentTopic($postData['id'] ?? 0);
        if (!$topic) return $this->rFail(lang('VTopicPage.data_empty'));

        $result = $this->updateTopicPage([
            'id' => $topic['id'],
            'status' => $postData['status'],
        ], [], ['status']);

        if ($result && $this->getTopicPageMachineCount(['topic_id' => $topic['id']]) > 0) {
            $this->sendTopicUpdateMq($topic['id']);
        }
        return $this->rU($result);
    }

    protected function appendMachineListToItem($item)
    {
        if (!is_array($item)) $item = $item->toArray();
        $payloadMap = $this->buildTopicMachinePayloadMap([intval($item['id'])]);
        $payload = $payloadMap[intval($item['id'])] ?? $this->emptyMachinePayload();
        return $this->appendMachinePayload($item, $payload);
    }

    protected function buildTopicMachinePayloadMap($topicIds)
    {
        $payloadMap = [];
        foreach ($topicIds as $topicId) {
            $payloadMap[$topicId] = $this->emptyMachinePayload();
        }
        if (!$topicIds) return $payloadMap;

        $bindList = $this->getTopicPageMachineList([['topic_id', 'in', $topicIds]], 0, 'topic_id,m_id,machine_id');
        $bindList = $bindList ? $bindList->toArray() : [];
        if (!$bindList) return $payloadMap;

        $mIds = array_values(array_unique(array_filter(array_column($bindList, 'm_id'))));
        $machineMap = [];
        if ($mIds) {
            $machineList = $this->getMachineList([['m_id', 'in', $mIds]], 0, 'm_id,machine_id,machine_name,online,street');
            if ($machineList) {
                foreach ($machineList as $machine) {
                    $machineMap[$machine['m_id']] = $machine;
                }
            }
        }

        foreach ($bindList as $bind) {
            $topicId = intval($bind['topic_id']);
            if (!isset($payloadMap[$topicId])) {
                $payloadMap[$topicId] = $this->emptyMachinePayload();
            }
            $machine = $machineMap[$bind['m_id']] ?? [];
            $bind['machine_name'] = $machine['machine_name'] ?? '';
            $bind['online'] = $machine['online'] ?? '';
            $bind['street'] = $machine['street'] ?? '';
            $payloadMap[$topicId]['machine_list'][] = $bind;
            $payloadMap[$topicId]['m_ids'][] = $bind['m_id'];
            $payloadMap[$topicId]['machine_ids'][] = $bind['machine_id'];
        }

        return $payloadMap;
    }

    protected function appendMachinePayload($item, $payload)
    {
        $machineList = $payload['machine_list'] ?? [];
        $mIds = array_values(array_unique($payload['m_ids'] ?? []));
        $machineIds = array_values(array_unique($payload['machine_ids'] ?? []));

        if (is_array($item)) {
            $item['machine_list'] = $machineList;
            $item['m_ids'] = $mIds;
            $item['machine_ids'] = $machineIds;
            return $item;
        }

        $item['machine_list'] = $machineList;
        $item['m_ids'] = $mIds;
        $item['machine_ids'] = $machineIds;
        return $item;
    }

    protected function appendTopicGroupPayload($item)
    {
        if (!is_array($item)) {
            $item = $item->toArray();
        }

        $item['base'] = [
            'top_logo' => $item['top_logo'] ?? '',
            'bg_url' => $item['bg_url'] ?? '',
            'maintenance_bg' => $item['maintenance_bg'] ?? '',
            'error_url' => $item['error_url'] ?? '',
            'closed_url' => $item['closed_url'] ?? '',
            'verification_url' => $item['verification_url'] ?? '',
        ];

        $item['pickup'] = [
            'pickup_url' => $item['pickup_url'] ?? '',
            'shipping_url' => $item['shipping_url'] ?? '',
            'pickup_qrcode_1' => $item['pickup_qrcode_1'] ?? '',
            'pickup_qrcode_2' => $item['pickup_qrcode_2'] ?? '',
        ];

        $item['pay'] = [
            'qr_code_url' => $item['qr_code_url'] ?? '',
            'scan_url' => $item['scan_url'] ?? '',
            'balance_url' => $item['balance_url'] ?? '',
            'card_url' => $item['card_url'] ?? '',
        ];

        return $item;
    }

    protected function emptyMachinePayload()
    {
        return [
            'machine_list' => [],
            'm_ids' => [],
            'machine_ids' => [],
        ];
    }

    protected function sendTopicUpdateMq($topicId)
    {
        $machineIds = $this->getTopicPageMachineColumn(['topic_id' => $topicId], 'machine_id');
        if (!$machineIds) return;

        foreach (array_unique($machineIds) as $machineId) {
            if (!$machineId) continue;
            $result = $this->sendToMachine(['machine_id' => $machineId], 'topicPageUpdate', ['topic_id' => $topicId]);
            if (!is_object($result)) actionLog([$machineId => $result], '主题更新MQ发送结果');
        }
    }

    protected function getCurrentTopic($topicId)
    {
        if (!$topicId) return null;
        $where = [['id', '=', $topicId]];
        $topic = $this->getTopicPageFind($where, '*');
        return $topic ? $topic->toArray() : null;
    }

    protected function parseMIds($mIds)
    {
        if (!$mIds) return [];
        if (is_array($mIds)) {
            $list = $mIds;
        } else {
            $list = explode(',', trim($mIds, ','));
        }
        $ids = [];
        foreach ($list as $id) {
            $id = intval(trim($id));
            if ($id > 0) $ids[] = $id;
        }
        return array_values(array_unique($ids));
    }
}
