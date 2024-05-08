<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/8
 * Time: 9:46
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineOnOffModel extends BaseModel
{
    protected $pk = "moo_id";
    protected $name = "machine_on_off";
}