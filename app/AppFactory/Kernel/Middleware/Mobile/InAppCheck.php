<?php
declare (strict_types = 1);

namespace app\AppFactory\Kernel\Mobile\Middleware;

use think\Request;

class InAppCheck
{
    /**
     * 处理请求
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        //
        if (preg_match('~micromessenger~i', $request->header('user-agent'))) {
            $request->InApp = 'WeChat';
        } else if (preg_match('~alipay~i', $request->header('user-agent'))) {
            $request->InApp = 'Alipay';
        }
        return $next($request);
    }
}
