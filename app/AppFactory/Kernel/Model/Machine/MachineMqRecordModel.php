<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/31
 * Time: 11:12
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineMqRecordModel extends BaseModel
{
    protected $pk = "mr_id";
    protected $name ="machine_mq_record";
}