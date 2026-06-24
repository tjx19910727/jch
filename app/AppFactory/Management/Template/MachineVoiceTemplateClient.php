<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/24
 */

namespace app\AppFactory\Management\Template;


use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Template\MachineVoiceTemplateTrait;
use app\AppFactory\Management\ManagementClient;

class MachineVoiceTemplateClient extends ManagementClient
{
    use MachineVoiceTemplateTrait;
    use MachineTrait;

    public function getVoiceList($where, $pageNum = 0, $field = "*", $order = "id desc")
    {
        $list = $this->getVoiceTemplateList($where, $pageNum, $field, $order);
        if ($list) {
            $voiceIds = [];
            foreach ($list as $item) {
                $voiceIds[] = intval($item['id']);
            }
            $voiceIds = array_values(array_unique(array_filter($voiceIds)));
            $payloadMap = $this->buildVoiceMachinePayloadMap($voiceIds);
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

    public function getVoiceFind($where, $field = "*")
    {
        $item = $this->getVoiceTemplateFind($where, $field);
        if ($item) {
            $item = $this->appendMachineListToItem($item->toArray());
        }
        return $this->rQ($item);
    }

    public function updateVoice($postData)
    {
        $voice = $this->getCurrentVoice($postData['id'] ?? 0);
        if (!$voice) return $this->rFail(lang('VMachineVoiceTemplate.data_empty'));

        $result = $this->updateVoiceTemplate($postData);
        if ($result && $this->getVoiceDetailCount(['voice_id' => $voice['id']]) > 0) {
            $this->sendVoiceUpdateMq($voice['id']);
        }
        return $this->rU($result);
    }

    public function delVoice($postData)
    {
        $voice = $this->getCurrentVoice($postData['id'] ?? 0);
        if (!$voice) return $this->rFail(lang('VMachineVoiceTemplate.data_empty'));

        $bindCount = $this->getVoiceDetailCount(['voice_id' => $voice['id']]);
        if ($bindCount > 0) return $this->rFail(lang('VMachineVoiceTemplate.data_assigned'));

        return $this->rD($this->delVoiceTemplate(['id' => $voice['id']]));
    }

    public function assignMachine($postData)
    {
        $voice = $this->getCurrentVoice($postData['id'] ?? 0);
        if (!$voice) return $this->rFail(lang('VMachineVoiceTemplate.data_empty'));

        $mIds = $this->parseMIds($postData['m_ids'] ?? '');
        $insertAll = [];
        if ($mIds) {
            $machineList = $this->getMachineList([['m_id', 'in', $mIds]], 0, 'm_id,machine_id');
            if (!$machineList || count($machineList) != count($mIds)) {
                return $this->rFail(lang('VMachineVoiceTemplate.data_invalid'));
            }
            foreach ($machineList as $item) {
                $insertAll[] = [
                    'voice_id' => $voice['id'],
                    'm_id' => $item['m_id'],
                    'machine_id' => $item['machine_id'],
                ];
            }
        }

        $this->startTrans();
        try {
            $this->delVoiceDetail(['voice_id' => $voice['id']]);
            if (!empty($insertAll)) {
                $result = $this->addVoiceDetailMore($insertAll);
                if ($result === false) {
                    $this->rollbackTrans();
                    return $this->rFail(lang('VMachineVoiceTemplate.data_assign_fail'));
                }
            }
            $this->commitTrans();
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }

        $this->sendVoiceUpdateMq($voice['id']);
        return $this->rAction(true);
    }

    public function setStatus($postData)
    {
        $voice = $this->getCurrentVoice($postData['id'] ?? 0);
        if (!$voice) return $this->rFail(lang('VMachineVoiceTemplate.data_empty'));

        $result = $this->updateVoiceTemplate([
            'id' => $voice['id'],
            'status' => $postData['status'],
        ], [], ['status']);

        if ($result && $this->getVoiceDetailCount(['voice_id' => $voice['id']]) > 0) {
            $this->sendVoiceUpdateMq($voice['id']);
        }
        return $this->rU($result);
    }

    /**
     * 复制语音模板
     * @param $postData
     * @return array|\think\response\Json
     */
    public function copyVoice($postData)
    {
        $voice = $this->getVoiceTemplateFind(['id' => $postData['id']]);
        if (!$voice) return $this->r(100, $this->lang("query_fail"));
        $voice = $voice->toArray();
        unset($voice['id']);
        $voice['title'] = $postData['title'] ?? ($voice['title'] . '_copy_' . time());
        $voice['created_at'] = date('Y-m-d H:i:s');
        $voice['manager_id'] = $this->manager['manager_id'] ?? $voice['manager_id'];
        $result = $this->addVoiceTemplate($voice);
        return $this->r(200, $this->lang("action_success"));
    }

    protected function appendMachineListToItem($item)
    {
        if (!is_array($item)) $item = $item->toArray();
        $payloadMap = $this->buildVoiceMachinePayloadMap([intval($item['id'])]);
        $payload = $payloadMap[intval($item['id'])] ?? $this->emptyMachinePayload();
        return $this->appendMachinePayload($item, $payload);
    }

    protected function buildVoiceMachinePayloadMap($voiceIds)
    {
        $payloadMap = [];
        foreach ($voiceIds as $voiceId) {
            $payloadMap[$voiceId] = $this->emptyMachinePayload();
        }
        if (!$voiceIds) return $payloadMap;

        $bindList = $this->getVoiceDetailList([['voice_id', 'in', $voiceIds]], 0, 'voice_id,m_id,machine_id');
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
            $voiceId = intval($bind['voice_id']);
            if (!isset($payloadMap[$voiceId])) {
                $payloadMap[$voiceId] = $this->emptyMachinePayload();
            }
            $machine = $machineMap[$bind['m_id']] ?? [];
            $bind['machine_name'] = $machine['machine_name'] ?? '';
            $bind['online'] = $machine['online'] ?? '';
            $bind['street'] = $machine['street'] ?? '';
            $payloadMap[$voiceId]['machine_list'][] = $bind;
            $payloadMap[$voiceId]['m_ids'][] = $bind['m_id'];
            $payloadMap[$voiceId]['machine_ids'][] = $bind['machine_id'];
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

    protected function emptyMachinePayload()
    {
        return [
            'machine_list' => [],
            'm_ids' => [],
            'machine_ids' => [],
        ];
    }

    protected function sendVoiceUpdateMq($voiceId)
    {
        $machineIds = $this->getVoiceDetailColumn(['voice_id' => $voiceId], 'machine_id');
        if (!$machineIds) return;

        foreach (array_unique($machineIds) as $machineId) {
            if (!$machineId) continue;
            $result = $this->sendToMachine(['machine_id' => $machineId], 'voiceTemplateUpdate', ['voice_id' => $voiceId]);
            if (!is_object($result)) actionLog([$machineId => $result], '语音模板更新MQ发送结果');
        }
    }

    protected function getCurrentVoice($voiceId)
    {
        if (!$voiceId) return null;
        $where = [['id', '=', $voiceId]];
        $voice = $this->getVoiceTemplateFind($where, '*');
        return $voice ? $voice->toArray() : null;
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
