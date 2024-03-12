<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/5
 * Time: 17:37
 */

namespace app\AppFactory\Kernel\Model\Activity;


use app\AppFactory\Kernel\Model\BaseModel;

class ActivityMachineModel extends BaseModel
{
    protected $pk = "am_id";
    protected $name = "activity_machine";
}