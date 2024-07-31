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
     * @return array|bool|string
     */
    public function getWxQr($manager_id)
    {
        try {
            $pid = $this->getAuthManagerValue(['manager_id' => $manager_id], 'pid');
            $pidList = $this->getParentIdList($pid);
            $pidList[] = $manager_id;
            $initResult = $this->initWxApp($pidList);
            if ($initResult !== true) return $initResult;
            $result = $this->wx_app->qrcode->temporary("1_$manager_id", 2 * 3600);
            return $this->r(200, '获取成功', $result);
        } catch (\Exception $e) {
            return $this->r(100,'微信返回错误信息：' . $e->getMessage());
        }
    }
}