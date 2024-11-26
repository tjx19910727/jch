<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/11/16
 * Time: 11:25
 */

namespace app\AppFactory\Wx\Official;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Wx\WxOfficialLoginTrait;
use app\AppFactory\Kernel\Traits\Wx\WxOfficialTrait;
use app\AppFactory\Kernel\Util\TDESUtil;
use app\AppFactory\Wx\WxBaseClient;
use app\wx\validate\VLogin;
use think\facade\Config;
use think\facade\Session;

class LoginClient extends WxBaseClient
{
    use WxOfficialTrait, WxOfficialLoginTrait;
    use AuthManagerTrait;
    use MachineTrait;

    /**
     * 扫码进入，发起静默授权
     * @param $postData
     */
    public function scanLogin($postData)
    {
        try {
            validate(VLogin::class)->scene("scanLogin")->check($postData);
        } catch (\Exception $e) {
            showMsg($e->getMessage());
        }
        if ($postData['time'] + 120 <= time()) {
            showMsg($this->lang("Login.time_over"));
        }
        $login = $this->getWxOfficialLoginFind(['id' => $postData['login_id']], 'id,wx_id,m_id,machine_id,login_token,login_type,status,create_time');
        if (!$login) {
            showMsg($this->lang("Login.wxLogin_no_data"));
        }
        $login = $login->toArray();
        if ($login['status'] == 2) {
            showMsg($this->lang("Login.status2"));
        }
        $status2 = $this->updateWxOfficialLogin(['id' => $postData['login_id'], 'status' => 2]);
        if ($status2) {
            $login['status'] = 2;
            // 管理后台扫码登录
            if ($login['login_type'] == 1)
                cache("wxLogin" . $login['login_type'] . $login['id'], null);
            // 终端扫码登录
            if ($login['login_type'] == 2) {
                $sendData = [
                    "status" => 2,
                ];
                $this->sendToMachine(['machine_id' => $login['machine_id']],'wxScanLogin',$sendData);
            }
            // 获取微信静默登录，跳转回调页
            $wx = $this->getWxOfficialFind(['id' => $login['wx_id'], 'status' => 1]);
            if (!$wx) {
                showMsg($this->lang("Login.wxOfficial_no_data"));
            }
            $wx = $wx->toArray();
            $this->getWxApp($wx);
            $callbackUrl = $this->getUrl("/wx/login/silentCallback/login_id/" . $postData['login_id']);
            $redirectUrl = $this->wx_app->oauth->scopes(['snsapi_base'])->redirect($callbackUrl);
            header("Location: $redirectUrl");
        }
    }

    /**
     * 静默授权回调
     * @param $postData
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Overtrue\Socialite\Exceptions\AuthorizeFailedException
     */
    public function silentCallback($postData)
    {
        actionLog($postData, '静默授权回调参数');
        if (cache("silentCallback" . $postData['code'] ?? "")) {
            showMsg($this->lang("Login.code_used"));
        }
        cache("silentCallback" . $postData['code'] ?? "", $postData, ['expire' => 120]);

        $login = $this->getWxOfficialLoginFind(['id' => $postData['login_id']], 'id,wx_id,login_token,login_type,status,create_time');
        if (!$login) {
            showMsg($this->lang("Login.wxLogin_no_data"));
        }
        $login = $login->toArray();
        if ($login['status'] == 3) {
            showMsg($this->lang("Login.status3"));
        }

        $status3 = $this->updateWxOfficialLogin(['id' => $postData['login_id'], 'status' => 2]);
        if ($status3) {
            // 获取微信静默登录，跳转回调页
            $wx = $this->getWxOfficialFind(['id' => $login['wx_id'], 'status' => 1]);
            if (!$wx) {
                showMsg($this->lang("Login.wxOfficial_no_data"));
            }
            $wx = $wx->toArray();
            $this->getWxApp($wx);
            $user = $this->wx_app->oauth->userFromCode($postData['code']);
            $response = $user->getTokenResponse();
            if (isset($response['openid'])) {
                $this->updateWxOfficialLogin(['id' => $postData['login_id'],'openid' => $response['openid']]);
                $urlData = ['openid' => $response['openid'],'login_id' => $postData['login_id']];
                $url = env("wx.SCAN_PAGE_URL") . "?" . http_build_query($urlData);
                // 跳转手机端选择账号登录
                header("Location: $url");
                die();
            }
        }
        showMsg($this->lang("Login.login_fail"));
    }

    /**
     * 用Openid获取账号列表
     * @param $postData
     * @return array|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUsersByOpenid($postData)
    {
        if (!isset($postData['openid']) || !$postData['openid']) return $this->r(100,$this->lang("Login.openid_can_not_empty"));
        $managerList = $this->getAuthManagerList(['openid' => $postData['openid'], 'status' => 1], 0, 'manager_id,nickname,account')->toArray();
        if ($managerList)
            return $this->r(200,$this->lang("query_success"),$managerList);
        return $this->r(100,$this->lang("query_fail"));
    }

    /**
     * 指定账号登录
     * @param $postData
     * @return array|\think\response\Json
     */
    public function managerLogin($postData)
    {
        if (!isset($postData['login_id']) || !$postData['login_id']) return $this->r(100,$this->lang("Login.login_id_can_not_empty"));
        if (!isset($postData['manager_id']) || !$postData['manager_id']) return $this->r(100,$this->lang("Login.manager_id_can_not_empty"));

        $login = $this->getWxOfficialLoginFind(['id' => $postData['login_id']]);
        if (!$login) {
            return $this->r(300, $this->lang("Login.wxLogin_no_data"));
        }
        $login = $login->toArray();
        if ($login['status'] == 3) {
            return $this->r(300, $this->lang("Login.status3"));
        }

        $manager = $this->getAuthManagerFind(['manager_id' => $postData['manager_id'], 'status' => 1]);
        unset($manager['password']);
        $update['id'] = $postData['login_id'];
        $update['manager_id'] = $manager['manager_id'];
        $update['account'] = $manager['account'];
        // 总后台登录，生成Token
        if ($login['login_type'] == 1) {
            $salt = Config::get('app.salt');
            Session::set("manager", $manager);
            $token_arr = [
                "session_id" => Session::getId(),
                "manager_id" => $manager['manager_id'],
                "timeout" => time(),
            ];
            $token = TDESUtil::encrypt(json_encode($token_arr), $salt);
            $update['status'] = 3;
            $update['login_token'] = $token;
        }
        // 终端登录，下发登录账号信息
        if ($login['login_type'] == 2) {
            $update['status'] = 3;
            $sendData = [
                "status" => 3,
                "manager_id" => $manager['manager_id'],
                "nickname" => $manager['nickname'],
                "account" => $manager['account'],
                "pic" => $manager['pic']
            ];
            $this->sendToMachine(['machine_id' => $login['machine_id']],'wxScanLogin',$sendData);
        }
        $this->updateWxOfficialLogin($update);
        cache("wxLogin" . $login['login_type'] . $login['id'], null);
        return $this->r(200,$this->lang("Login.login_success"));
    }
}