<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/3
 * Time: 13:44
 */

namespace app\AppFactory\Management\Login;


use app\AppFactory\Kernel\Traits\Wx\WxOfficialLoginTrait;
use app\AppFactory\Kernel\Traits\Wx\WxOfficialTrait;
use app\AppFactory\Kernel\Util\TDESUtil;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Session;

class LoginClient extends ManagementClient
{
    use AuthManagerTrait;
    use WxOfficialTrait,WxOfficialLoginTrait;

    public function login($data)
    {
        $this->manager = $this->getAuthManagerFind(['account' => $data['account']]);
        if (!$this->manager) return $this->rFail($this->lang("VLogin.account_not_exist"));
        if ($this->manager['password'] != md5($data['password'].$this->salt))
            return returnState(100,$this->lang("VLogin.account_pwd_incorrect"));
        if ($this->manager['status'] == 2) return returnState(100,$this->lang("VLogin.account_disabled"));
        // 保存用户数据并生成TOKEN
        return self::setLoginInfo();
    }

    /**
     * 未登录状态下通过旧密码修改新密码
     * @param array $data
     * @return array|\think\response\Json
     */
    public function changePassword($data)
    {
        $manager = $this->getAuthManagerFind(['account' => $data['account']]);
        if (!$manager) return $this->rFail($this->lang("VLogin.account_not_exist"));
        if ($manager['password'] != md5($data['old_password'] . $this->salt)) {
            return returnState(100, $this->lang("VLogin.pwd_incorrect"));
        }
        if ($data['new_password'] !== $data['confirm_password']) {
            return returnState(100, $this->lang("VLogin.password_not_match"));
        }
        if ($manager['status'] == 2) return returnState(100, $this->lang("VLogin.account_disabled"));
        $this->manager = $manager;
        return $this->rU($this->updateAuthManager([
            'manager_id' => $manager['manager_id'],
            'password' => $data['new_password'],
        ]));
    }

    /**
     *  1-1. 保存当前登录用户信息，生成Token
     * @return array|string
     */
    public function setLoginInfo()
    {
        if(isset($this->manager['password'])) unset($this->manager['password']);
        Session::set("manager",$this->manager);
        $token = self::make_token();
        return returnState(200,$this->lang("VLogin.login_success"),$token);
    }

    /**
     * 1-1-1. 生成会话Token
     * @return string
     */
    public function make_token()
    {
        $token_arr = [
            "session_id" => Session::getId(),
            "manager_id" => $this->manager['manager_id'],
            "timeout" => time(),
        ];
        return TDESUtil::encrypt(json_encode($token_arr),$this->salt);
    }

    /**
     * 获取微信扫码登录链接
     * @param $postData
     * @return array|\think\response\Json
     */
    public function getWxLoginUrl($postData)
    {
        if (!empty($postData['official'])) {
            $config = $this->getWxOfficialFind(['id' => $postData['official']]);
        } else {
            $config = $this->getWxOfficialFind(['status' => 1], '*', 'id desc');
        }
        if (!$config) {
            return $this->r(100,$this->lang("VWxLogin.wx_no_data"));
        }
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
        $loginUrl = $this->getUrl("/wx/login/scanLogin/login_id/$id/time/" . time());
        $this->updateWxOfficialLogin(['id' => $id,"login_url" => $loginUrl]);
        return $this->r(200,$this->lang("action_success"),["id" => $id,'status' => 1,"login_url" => $loginUrl]);
    }

    /**
     * 检查微信扫码登录状态
     * @param $postData
     * @return array|\think\response\Json
     */
    public function checkWxLoginStatus($postData)
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
        if ($loginFind['create_time'] + 120 <= time()) {
            return $this->r(100,'二维码已过期，请刷新二维码重试');
        }
        $postData['id'] = $loginFind['id'];
        $login = $this->getWxOfficialLoginFind(['id' => $postData['id']], 'id,login_token,status,create_time');
        if (!$login) return $this->r(100,$this->lang("query_fail"));
        $login = $login->toArray();
        if ($login['create_time'] + 120 <= time()) {
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
