<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/8/19
 * Time: 10:00
 */

namespace app\AppFactory\Management\Machine;

use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Traits\Machine\MachineRemarkTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class MachineRemarkClient extends ManagementClient
{
    use MachineTrait;
    use MachineRemarkTrait;

    public function addRemark($postData)
    {
        $mId = intval($postData['m_id'] ?? 0);
        if ($mId <= 0) {
            return $this->rValidate('设备不能为空');
        }
        if (!$this->assertMachinePermit($mId)) {
            return $this->rValidate('没有该设备的操作权限');
        }
        $postData['m_id'] = $mId;
        $this->fillMachineFields($postData);
        if (!isset($postData['creator'])) {
            $postData['creator'] = $this->manager['manager_id'] ?? 0;
        }
        if (empty($postData['type'])) {
            $postData['type'] = 1;
        }
        $result = $this->addMachineRemark($postData);
        return $this->rA($result);
    }

    public function updateRemark($postData)
    {
        $id = intval($postData['id'] ?? 0);
        if ($id <= 0) {
            return $this->rValidate('id不能为空');
        }
        $record = $this->getMachineRemarkFind(['id' => $id], 'id,m_id');
        if (!$record) {
            return $this->rValidate('备注记录不存在');
        }
        $record = is_object($record) ? $record->toArray() : $record;
        $oldMId = intval($record['m_id'] ?? 0);
        if (!$this->assertMachinePermit($oldMId)) {
            return $this->rValidate('没有该设备的操作权限');
        }
        $newMId = intval($postData['m_id'] ?? 0);
        if ($newMId > 0 && $newMId !== $oldMId && !$this->assertMachinePermit($newMId)) {
            return $this->rValidate('没有该设备的操作权限');
        }
        unset($postData['id']);
        $this->fillMachineFields($postData);
        $result = $this->updateMachineRemark(
            $postData,
            ['id' => $id],
            ['m_id', 'machine_id', 'type', 'remark', 'update_time']
        );
        return $this->rU($result);
    }

    public function delRemark($postData)
    {
        $id = intval($postData['id'] ?? 0);
        if ($id <= 0) {
            return $this->rValidate('id不能为空');
        }
        $record = $this->getMachineRemarkFind(['id' => $id], 'id,m_id');
        if (!$record) {
            return $this->rValidate('备注记录不存在');
        }
        $record = is_object($record) ? $record->toArray() : $record;
        if (!$this->assertMachinePermit(intval($record['m_id'] ?? 0))) {
            return $this->rValidate('没有该设备的操作权限');
        }
        $result = $this->delMachineRemark(['id' => $id]);
        return $this->rD($result);
    }

    /**
     * 校验设备是否在当前账号可见范围内（超管不限制）
     */
    private function assertMachinePermit($mId)
    {
        $permitted = $this->app->machine->resolvePermittedMachineIds();
        if ($permitted === null) {
            return true;
        }
        return in_array(intval($mId), $permitted, true);
    }

    /**
     * 根据 m_id 回填设备编号
     */
    private function fillMachineFields(&$postData)
    {
        if (empty($postData['m_id'])) {
            return;
        }
        $machine = MachineModel::getFind(['m_id' => $postData['m_id']], 'machine_id');
        if ($machine) {
            $machine = is_object($machine) ? $machine->toArray() : $machine;
            $postData['machine_id'] = $machine['machine_id'] ?? '';
        }
    }
}
