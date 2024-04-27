<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineInfoTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\VMachineInfo;

class MachineInfoClient extends ManagementClient
{
    use MachineInfoTrait;

    public function updateMore($postData)
    {
        $this->startTrans();
        foreach ($postData['miList'] as $key => $value) {
            try {
                validate(VMachineInfo::class)->scene("updateMore")->check($value);
            } catch (\Exception $e) {
                $this->rollbackTrans();
                return $this->rValidate($e->getMessage());
            }
            $flag[] = $this->updateMachineInfo($value,['m_id' => $value['m_id']]);
        }
        $check = $this->checkFlag($flag);
        return $this->checkTrans($check);
    }
}