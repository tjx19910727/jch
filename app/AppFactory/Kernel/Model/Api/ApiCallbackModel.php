<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/19
 * Time: 16:54
 */

namespace app\AppFactory\Kernel\Model\Api;


use app\AppFactory\Kernel\Model\BaseModel;

class ApiCallbackModel extends BaseModel
{
    protected $pk = "ac_id";
    protected $name = "api_callback";
}