<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 16:42
 */

namespace app\AppFactory\Kernel\Middleware\Management;


use app\AppFactory\Kernel\Support\TDESUtil;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use think\facade\Config;
use think\Request;
use think\Response;

class CheckLogin
{
    use AuthManagerTrait;

    public function handle(Request $request, \Closure $next) {

        $token = $request->header("token");
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
        return $next($request);
    }


    public function check_token($token = '')
    {
        $key = Config::get("app.salt");
        $token_arr = TDESUtil::decrypt($token,$key);
        $token_arr = json_decode($token_arr,true);
        if(time() - $token_arr['timeout'] >= 24 * 3600 * 7){  // Token超时，7天
            return "会话超时，请重新登陆";
        }
        return $token_arr;
    }
}