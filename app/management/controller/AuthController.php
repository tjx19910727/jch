<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 20:10
 */

namespace app\Management\controller;


use app\AppFactory\Kernel\Middleware\Management\CheckLogin;
use app\AppFactory\Kernel\Support\TDESUtil;
use app\BaseController;
use think\facade\Config;
use think\facade\Session;
use app\AppFactory\AppFactory;

class AuthController extends BaseController
{
    protected $manager;
    protected $middleware = [CheckLogin::class];
    protected $currentMenu = [];

    protected function initialize()
    {
        $token = request()->header("token");
        if (!$token) $token = input("token");
        if(!$token){
            json(['state' => 100,'msg' => "令牌不能为空，请重新登录"])->send();
            die();
        }
        $token_arr = $this->check_token($token);
        if(is_string($token_arr)){
            json(['state' => 98,'msg' => $token_arr])->send();
            die();
        }
        session_id($token_arr['session_id']);
        $this->app = AppFactory::management();
        $this->manager = $this->app->authManager->getAuthManagerFind(['manager_id' => $token_arr['manager_id']]);
        if (!$this->manager) {
            json(['state' => 100,'msg' => "查无账号信息"])->send();
            die();
        }
        $this->manager = $this->manager->getData();
//        $this->manager['watch_store_ids'] = $this->app->storeClerk->getStoreClerkColumn(['manager_id' => $this->manager['manager_id'],'clerk_type' => 2],'store_id');
        $this->app = AppFactory::management($this->manager);
        $checkAuthNode = $this->app->authManagerRole->checkAuthNode();
        if (!is_array($checkAuthNode)) {
            json($checkAuthNode->getData())->send();
            die();
        }
        $this->currentMenu = $checkAuthNode;
    }

    public function check_token($token = '')
    {
        $key = Config::get("app.salt");
        $token_arr = TDESUtil::decrypt($token,$key);
        $token_arr = json_decode($token_arr,true);
        if(time() - $token_arr['timeout'] >= 24 * 3600 * 365){  // Token超时，7天
            return "会话超时，请重新登陆";
        }
        return $token_arr;
    }
}