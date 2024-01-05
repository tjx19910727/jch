<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/14
 * Time: 11:44
 */

namespace app\AppFactory\Kernel\Traits\OpenPlatform;


use app\AppFactory\Kernel\Model\OpenPlatform\OpenPlatformWxInfoModel;

trait OpenPlatformWxInfoTrait
{
    public function getOpenPlatformWxInfoList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return OpenPlatformWxInfoModel::getList($where,$pageNum,$field,$order);
    }

    public function getOpenPlatformWxInfoFind($where,$field = "*",$order = "")
    {
        return OpenPlatformWxInfoModel::getFind($where,$field,$order);
    }

    public function addOpenPlatformWxInfo($insert)
    {
        $wi = OpenPlatformWxInfoModel::create($insert);
        return $wi->wi_id;
    }

    public function updateOpenPlatformWxInfo($update, $where = [], $field = [])
    {
        return OpenPlatformWxInfoModel::update($update,$where,$field);
    }

    public function getOpenPlatformWxInfoValue($where,$value)
    {
        return OpenPlatformWxInfoModel::getFieldValue($where,$value);
    }

    public function setAuthInfo($authInfo,$wx,$manager)
    {
        $authInfo["service_type_info"] = $authInfo['service_type_info']["id"];
        $authInfo["verify_type_info"] = $authInfo['verify_type_info']['id'];
        $updateAuthInfo = $authInfo;
        $updateAuthInfo['business_info'] = json_encode($updateAuthInfo['business_info'],JSON_UNESCAPED_UNICODE);
//        $updateAuthInfo['update_time'] = date("Y-m-d H:i:s");
        $updateAuthInfo['manager_id'] = $manager['manager_id'] ?? 0;
        $aInfo = $this->getOpenPlatformWxInfoFind(['wx_id' => $wx['wx_id']]);
        if ($aInfo) {
            $updateAuthInfo['wi_id'] = $aInfo['wi_id'];
            $this->updateOpenPlatformWxInfo($updateAuthInfo);
        } else {
            $updateAuthInfo['wx_id'] = $wx['wx_id'];
            $updateAuthInfo['wi_id'] = $this->addOpenPlatformWxInfo($updateAuthInfo);
        }
    }
}
