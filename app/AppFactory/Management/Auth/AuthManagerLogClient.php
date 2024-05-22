<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/22
 * Time: 12:02
 */

namespace app\AppFactory\Management\Auth;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerLogTrait;
use app\AppFactory\Management\ManagementClient;

class AuthManagerLogClient extends ManagementClient
{
    use AuthManagerLogTrait;
}