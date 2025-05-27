<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:10
 */

namespace app\AppFactory\Management\Auth;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Wx\WxOfficialTrait;
use app\AppFactory\Management\ManagementClient;

class AuthManagerClient extends ManagementClient
{
    use AuthManagerTrait;
    use WxOfficialTrait;

    public function updateSelfPwd($postData)
    {
        if ($this->manager['password'] != md5($postData['old_pwd'] .$this->salt )) {
            return $this->r(100,$this->lang("VLogin.pwd_incorrect"));
        }
        $update['password'] = $postData['password'];
        $update['manager_id'] = $this->manager['manager_id'];
        return $this->rU($this->updateAuthManager($update));
    }

    /**
     * 获取微信公众号带参二维码
     * @param $manager_id
     * @param $unbind
     * @return array|bool|string
     */
    public function getWxQr($manager_id,$unbind = 0)
    {
        $manager = $this->getAuthManagerFind(['manager_id' => $manager_id]);
        if ($manager['openid'] && $unbind) {
            $result = $this->updateAuthManager(['manager_id' => $manager_id,'wx_id' => 0,"openid" => ""]);
            if ($result) {
                return $this->r(200,$this->lang("VWxOfficial.unbind_success"));
            }
        }
        try {
            $pid = $this->getAuthManagerValue(['manager_id' => $manager_id], 'pid');
            if (!$pid) {
                $pid = $this->getAuthManagerValue(['manager_id' => $manager_id],'creator');
            }
            $pidList = $this->getParentIdList($pid);
            $pidList[] = $manager_id;
            actionLog($pidList,'创建人树');
            $where[] = ['creator', 'in', $pidList];
            $where['status'] = 1;
            $config = $this->getWxOfficialFind($where,'*','id desc');
            if (!$config) {
                actionLog($this->getLS(),'查询配置SQL');
                return $this->r(100, $this->lang("VWxOfficial.official_no_data"));
            }
            $config = $config->toArray();
            $qrScene = $config['id'] . "_2_" . $manager_id;
            $this->getWxApp($config);
            $result = $this->wx_app->qrcode->temporary($qrScene, 5 * 60);
            if (isset($result['ticket'])) {
                if ($config['status'] != 1) $this->updateWxOfficial(['id' => $config['id'], 'status' => 1]);
                $url = $this->wx_app->qrcode->url($result['ticket']);
                return $this->r(200, 'success', $url);
            }
            if ($config['status'] != 2) $this->updateWxOfficial(['id' => $config['id'], 'status' => 2]);
            return $this->r(100, 'fail', $result['errorMsg'] ?? "");
        } catch (\Exception $e) {
            return $this->r(100,'微信返回错误信息：' . $e->getMessage());
        }
    }
}