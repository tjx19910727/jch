<?php


namespace app\AppFactory\Kernel\Middleware\Http;


use app\Request;
//use app\models\user\User;
//use app\models\user\UserToken;
//use crmeb\exceptions\AuthException;
//use crmeb\interfaces\MiddlewareInterface;
//use crmeb\repositories\UserRepository;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
//use think\exception\DbException;

/**
 * token验证中间件
 * Class AuthTokenMiddleware
 * @package app\http\middleware
 */
class AuthTokenMiddleware implements \app\AppFactory\Kernel\Middleware\MiddlewareInterface
{
    public function handle(Request $request, \Closure $next, bool $force = true)
    {
        $authInfo = null;
        $token = trim(ltrim($request->header('Authori-zation'), 'Bearer'));
        if(!$token)  $token = input("token");




        return $next($request);
    }
}