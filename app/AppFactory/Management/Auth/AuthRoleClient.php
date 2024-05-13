<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:15
 */

namespace app\AppFactory\Management\Auth;


use app\AppFactory\Kernel\Traits\Auth\AuthOrganizationRoleTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthRoleTrait;
use app\AppFactory\Management\ManagementClient;

class AuthRoleClient extends ManagementClient
{
    use AuthRoleTrait,AuthOrganizationRoleTrait;
}