<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/23
 * Time: 10:00
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class OtaVersionModel extends BaseModel
{
    protected $pk = "ov_id";
    protected $name = "ota_version";
}
