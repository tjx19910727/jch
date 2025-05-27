<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/5/15
 * Time: 15:13
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineConfigLangTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\Machine\VMachineConfigLang;

class MachineConfigLangClient extends ManagementClient
{
    use MachineTrait;
    use MachineConfigLangTrait;

    /**
     * 添加设备配置多语言数据
     * @param $postData
     * @return array|\think\response\Json
     */
    public function addMcl($postData)
    {
        $mcl = $this->getMachineConfigLangFind(['mc_id' => $postData['mc_id'],'lang' => $postData['lang']]);
        if ($mcl) return $this->rFail($this->lang("VMachineConfigLang.is_exist"));
        $result = $this->addMachineConfigLang($postData);
        return $this->rA($result);
    }

    /**
     * 批量修改设备配置多语言数据
     * @param $postData
     * @return array|\think\response\Json
     */
    public function updateMoreMcl($postData)
    {
        try {
            foreach ($postData['mcList'] as $key => $value) {
                validate(VMachineConfigLang::class)->scene("mcList")->check($value);
                $result = $this->updateMachineConfigLang($value, ['m_id' => $value['m_id'],'lang' => $value['lang']]);
                if ($result) {
                    $mc = $this->getMachineConfigLangFind(['m_id' => $value['m_id']], "machine_id");
                    $mc = $mc->toArray();
                    $this->sendToMachine(['machine_id' => $mc['machine_id']],'updateMachineConfig');
                } else {
                    return $this->r(100, $this->lang("update_fail"), $value);
                }
            }
            return $this->r(200, $this->lang("update_success"));
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->r(100,$this->lang($e->getMessage()));
        }
    }

}