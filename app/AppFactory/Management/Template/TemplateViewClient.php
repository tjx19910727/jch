<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 14:45
 */

namespace app\AppFactory\Management\Template;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineViewTrait;
use app\AppFactory\Kernel\Traits\Template\TemplateViewTrait;
use app\AppFactory\Management\ManagementClient;

class TemplateViewClient extends ManagementClient
{
    use TemplateViewTrait,MachineTrait,MachineViewTrait;

    public function checkAdd($postData)
    {
//        $plugin_data = json2arr($postData['plugin_data']);
//        try {
//            validate(VTemplateView::class)->scene("plugin_data")->check($plugin_data);
//        } catch (ValidateException $e) {
//            return $this->rFail($e->getMessage());
//        }
        return $this->rA($this->addTemplateView($postData));
    }

    /**
     * 复制视图
     * @param $postData
     * @return array|\think\response\Json
     */
    public function copy($postData)
    {
        $tv = $this->getTemplateViewFind(['id' => $postData['id']]);
        if (!$tv) return $this->r(100,$this->lang("query_fail"));
        $tv = $tv->toArray();
        unset($tv['id']);
        $tv['name'] = $postData['name'];
        $result = $this->addTemplateView($tv);
        return $this->rA($result);
    }

    /**
     * 修改模板视图，修改成功后，检查已绑定的设备下发更新通知
     * @param $postData
     * @return array|\think\response\Json
     */
    public function updateTv($postData)
    {
        $result = $this->updateTemplateView($postData);
        if ($result) {
            $mvList = $this->getMachineViewList(['view_id' => $postData['id']],0,'m_id,machine_id');
            if ($mvList) {
                $mvList = $mvList->toArray();
                foreach ($mvList as $mv) {
                    // 发送触发模板视图更新
                    $this->sendToMachine($mv,"updateMachineView");
                }
            }
            return $this->rSuccess();
        }
        return $this->rFail();
    }

    /**
     * 删除模板视图，并下发通知设备更新
     * @param $postData
     * @return array|\think\response\Json
     */
    public function delTv($postData)
    {
        try {
            $this->startTrans();
            $result = $this->delTemplateView($postData);
            if ($result) {
                $mvList = $this->getMachineViewList(['view_id' => $postData['id']], 0, 'm_id,machine_id');
                if ($mvList) {
                    $mvList = $mvList->toArray();
                    foreach ($mvList as $mv) {
                        // 发送触发模板视图更新
                        $this->sendToMachine($mv, "updateMachineView");
                    }
                }
                $this->delMachineView(['view_id' => $postData['id']]);
                $this->commitTrans();
                return $this->r(200, $this->lang("del_success"));
            }
            $this->rollbackTrans();
            return $this->r(100, $this->lang("del_fail"));
        } catch (\Exception $e) {
            actionException($e,1);
            $this->rollbackTrans();
            return $this->rTryCatch($e->getMessage());
        }
    }
}