<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/6
 * Time: 14:59
 */

namespace app\AppFactory\Management\Auth;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrganizationRoleTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrganizationTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrgMachineChannelTrait;
use app\AppFactory\Management\ManagementClient;

class AuthOrganizationClient extends ManagementClient
{
    use AuthOrganizationTrait,AuthOrganizationRoleTrait,AuthManagerTrait,AuthOrgMachineChannelTrait;
}