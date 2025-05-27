<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/3
 * Time: 10:10
 */

namespace app\AppFactory\Management\Wx;


use app\AppFactory\Kernel\Traits\Wx\WxOfficialTrait;
use app\AppFactory\Management\ManagementClient;

class WxOfficialClient extends ManagementClient
{
    use WxOfficialTrait;

    public function getQrCode($postData)
    {
        try {
            $config = $this->getWxOfficialFind(['id' => $postData['id']]);
            if (!$config) return $this->r(100, $this->lang("VWxOfficial.official_no_data"));
            $config = $config->toArray();
            $qrScene = $postData['id'] . "_1_" . ($postData['manager_id'] ?? $this->manager['manager_id']);
            $this->getWxApp($config);
            $result = $this->wx_app->qrcode->temporary($qrScene, 5 * 60);
            actionLog($result, '获取公众号二维码返回结果');
            if (isset($result['ticket'])) {
                if ($config['status'] != 1) $this->updateWxOfficial(['id' => $postData['id'], 'status' => 1]);
                $url = $this->wx_app->qrcode->url($result['ticket']);
                return $this->r(200, 'success', $url);
            }
            if ($config['status'] != 2) $this->updateWxOfficial(['id' => $postData['id'], 'status' => 2]);
            return $this->r(100, 'fail', $result['errorMsg'] ?? "");
        } catch (\Exception $e) {
            return $this->rTryCatch($e->getMessage());
        }
    }
}