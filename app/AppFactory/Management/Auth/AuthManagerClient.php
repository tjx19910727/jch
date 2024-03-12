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
            $initResult = $this->initWxApp();
            if ($initResult !== true) return $initResult;
            $wx = $this->getOpenPlatformWxFind([['creator', 'in', $pidList], 'status' => 1, 'wx_type' => 1], '*', 'wx_id desc');
            if (!$wx) return $this->rFail('查无公众号授权信息');
            $app = $this->opApp->officialAccount($wx['authorizer_appid'], $wx['authorizer_refresh_token']);
            $result = $app->qrcode->temporary("1_$manager_id", 2 * 3600);
            return $this->r(200, '获取成功', $result);
        } catch (\Exception $e) {
            return $this->rValidate($e->getMessage());
        }
    }
}