<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/1
 * Time: 17:14
 */

namespace app\AppFactory\Kernel\Model\UpdateLog;


use app\AppFactory\Kernel\Model\BaseModel;

class UpdateLogModel extends BaseModel
{
    protected $pk = "id";
    protected $name = "update_log";
}