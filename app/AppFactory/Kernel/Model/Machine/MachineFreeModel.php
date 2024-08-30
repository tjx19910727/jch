<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/26
 * Time: 15:32
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineFreeModel extends BaseModel
{
    protected $pk = "mf_id";
    protected $name = "machine_free";
}