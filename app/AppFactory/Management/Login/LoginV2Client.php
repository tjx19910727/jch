<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/5/29
 * Time: 10:00
 */

namespace app\AppFactory\Management\Login;


use app\AppFactory\Kernel\Traits\Wx\WxOfficialLoginTrait;
use app\AppFactory\Kernel\Traits\Wx\WxOfficialTrait;
use app\AppFactory\Management\ManagementClient;

class LoginV2Client extends ManagementClient
{
    use WxOfficialTrait,WxOfficialLoginTrait;

    /**
     * 获取微信扫码登录链接V2（直接进入公众号事件）
     * @param $postData
     * @return array|\think\response\Json
     */
    public function getWxLoginUrlV2($postData)
    {
        if (!empty($postData['official'])) {
            $config = $this->getWxOfficialFind(['id' => $postData['official']]);
        } else {
            $config = $this->getWxOfficialFind(['status' => 1], '*', 'id desc');
        }
        if (!$config) {
            return $this->r(100,$this->lang("VWxLogin.wx_no_data"));
        }
        $config = $config->toArray();
        if ($config['status'] == 2) return $this->r(100,$this->lang("VWxLogin.wx_status2"));
        $insert = [
            "wx_id" => $config['id'],
            "app_id" => $config['app_id'],
            "ip" => request()->ip(),
            "login_type" => 1,
            "ao_id" => $config['ao_id'],
        ];
        $id = $this->addWxOfficialLogin($insert);
        if (!$id) return $this->r(100,$this->lang("action_fail"));
        try {
            $this->getWxApp($config);
            // V2 场景值：公众号ID_业务类型_登录记录ID，type=5 表示管理后台扫码登录
            $qrScene = $config['id'] . "_5_" . $id;
            $result = $this->wx_app->qrcode->temporary($qrScene, 2 * 60);
            if (!isset($result['ticket'])) {
                return $this->r(100, $result['errorMsg'] ?? $this->lang("action_fail"));
            }
            $loginUrl = $this->wx_app->qrcode->url($result['ticket']);
            $this->updateWxOfficialLogin(['id' => $id,"login_url" => $loginUrl]);
            return $this->r(200,$this->lang("action_success"),["id" => $id,'status' => 1,"login_url" => $loginUrl, "ticket" => $result['ticket']]);
        } catch (\Exception $e) {
            return $this->r(100,'微信返回错误信息：' . $e->getMessage());
        }
    }
}
