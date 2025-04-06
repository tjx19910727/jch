<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/30
 * Time: 11:04
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineErrorCodeModel extends BaseModel
{
    protected $pk = "me_id";
    protected $name = "machine_error_code";
}