<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/5/15
 * Time: 15:13
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineLangTrait;
use app\AppFactory\Management\ManagementClient;

class MachineLangClient extends ManagementClient
{
    use MachineLangTrait;

    /**
     * 添加设备主体语言
     * @param $postData
     * @return array|\think\response\Json
     */
    public function addMl($postData)
    {
        $check = $this->getMachineLangFind(['m_id' => $postData['m_id'],"lang" => $postData['lang']]);
        if ($check) return $this->rFail($this->lang("VMachineLang.is_exist"));
        $result = $this->addMachineLang($postData);
        return $this->rA($result);
    }

    /**
     * 修改设备主体语言
     * @param $postData
     */
    public function updateMl($postData)
    {
        if (isset($postData['m_id'])) unset($postData['m_id']);
        if (isset($postData['machine_id'])) unset($postData['machine_id']);
        if (isset($postData['lang'])) unset($postData['lang']);
        $this->updateMachineLang($postData);
    }
}