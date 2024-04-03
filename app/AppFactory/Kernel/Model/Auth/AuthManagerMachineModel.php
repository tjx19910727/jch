<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/14
 * Time: 17:14
 */

namespace app\AppFactory\Kernel\Model\Auth;


use app\AppFactory\Kernel\Model\BaseModel;

class AuthManagerMachineModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "auth_manager_machine";
}