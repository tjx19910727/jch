<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:14
 */

namespace app\AppFactory\Management\Auth;


use app\AppFactory\Kernel\Traits\Auth\AuthNodeTrait;
use app\AppFactory\Management\ManagementClient;

class AuthNodeClient extends ManagementClient
{
    use AuthNodeTrait;
}