<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 17:08
 */

namespace app\AppFactory\Kernel\Middleware;



use app\Request;

interface MiddlewareInterface
{
    public function handle(Request $request, \Closure $next);
}