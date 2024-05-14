<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Exceptions\ValidateException;
use app\AppFactory\Kernel\Traits\Machine\MachineHelpTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\Machine\VMachineHelp;

class MachineHelpClient extends ManagementClient
{
    use MachineHelpTrait;

    /**
     * 批量添加
     * @param $postData
     * @return array|string
     */
    public function addMore($postData)
    {
        $insert = [
            "m_id" => $postData['m_id'],
            "machine_id" => $postData['machine_id'],
        ];
        $list = json2arr($postData['help_list']);
        $insertAll = [];
        foreach ($list as $key => $value) {
            try {
                validate(VMachineHelp::class)->scene("addMore")->check($value);
            } catch (ValidateException $e) {
                return $this->rValidate($e->getMessage());
            }
            $insertAll[] = array_merge($insert, $value);
        }
        return $this->rA($this->addMoreMachineHelp($insertAll));
    }

    /**
     * 批量修改
     * @param $postData
     * @return array|bool|string
     */
    public function updateMore($postData)
    {
        $postData['help_list'] = json2arr($postData['help_list']);
        $this->startTrans();
        foreach ($postData['help_list'] as $key => $value) {
            try {
                validate(VMachineHelp::class)->scene("update")->check($value);
            } catch (ValidateException $e) {
                $this->rollbackTrans();
                return $this->rValidate($e->getMessage());
            }
            $flag[] = $this->updateMachineHelp($value);
        }
        $result = $this->checkFlag($flag);
        return $this->checkTrans($result);
    }
}