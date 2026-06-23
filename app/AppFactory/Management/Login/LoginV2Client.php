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

    /**
     * 获取微信扫码登录二维码（仿设备端 wxLoginQrCode 模式）
     * 与 getWxLoginUrlV2 的区别：不调用微信生成参数二维码，直接返回 H5 扫码链接
     * 前端用返回的 id 轮询 checkWxLoginStatus，倒计时120s
     * @param array $postData 可选 official 指定公众号
     * @return array|\think\response\Json
     */
    public function getWxScanQrCode($postData = [])
    {
        if (!empty($postData['official'])) {
            $config = $this->getWxOfficialFind(['id' => $postData['official']]);
        } else {
            $config = $this->getWxOfficialFind(['status' => 1], '*', 'id desc');
        }
        if (!$config) {
            return $this->r(100, $this->lang("VWxLogin.wx_no_data"));
        }
        if ($config['status'] == 2) {
            return $this->r(100, $this->lang("VWxLogin.wx_status2"));
        }
        $config = $config->toArray();
        $insert = [
            "wx_id"      => $config['id'],
            "app_id"     => $config['app_id'],
            "ip"         => request()->ip(),
            "login_type" => 1,
            "ao_id"      => $config['ao_id'],
        ];
        $id = $this->addWxOfficialLogin($insert);
        if (!$id) {
            return $this->r(100, $this->lang("action_fail"));
        }
        $ticket = "/wx/login/scanLogin/login_id/$id/time/" . time();
        $loginUrl = $this->getUrl($ticket);
        $loginUrl = $loginUrl.'?ticket='.md5($ticket);
        $this->updateWxOfficialLogin(['id' => $id, "login_url" => $loginUrl]);
        return $this->r(200, $this->lang("action_success"), [
            "id"        => $id,
            "login_url" => $loginUrl,
            "ticket" =>md5($ticket)
        ]);
    }

    /**
     * 检查微信扫码登录状态V2（通过 id 直查，配合 getWxScanQrCode 使用）
     * @param array $postData 需传 id
     * @return array|\think\response\Json
     */
    public function checkWxLoginStatusV2($postData)
    {
        $ticket = trim($postData['ticket'] ?? '');
        if (!$ticket) {
            return $this->r(100,'ticket不能为空');
        }
        $where = [];
        $where[] = ['login_type', '=', 1];
        $where[] = ['login_url', 'like', '%' . $ticket . '%'];
        $loginFind = $this->getWxOfficialLoginFind($where, 'id,create_time', 'id desc');
        if (!$loginFind) {
            return $this->r(100,'ticket无效或登录记录不存在');
        }
        $loginFind = $loginFind->toArray();
        if ($loginFind['create_time'] + 60 <= time()) {
            return $this->r(100,'二维码已过期，请刷新二维码重试');
        }
        $postData['id'] = $loginFind['id'];
        $login = $this->getWxOfficialLoginFind(['id' => $postData['id']], 'id,login_token,status,create_time');
        if (!$login) return $this->r(100,$this->lang("query_fail"));
        $login = $login->toArray();
        if ($login['create_time'] + 60 <= time()) {
            return $this->r(100,'二维码已过期，请刷新二维码重试');
        }
        if ($login['status'] == 3) {
            if(!empty($login['login_token'])){
                return returnState(200, 'VLogin.login_success', $login['login_token']);
            }
             return $this->r(100,'登录失败，登录Token不存在');
        }elseif($login['status'] == 2){
            return $this->r(100,'已扫码待确认');
        }else{
            return $this->r(100,'未扫码');
        }
    }
}
