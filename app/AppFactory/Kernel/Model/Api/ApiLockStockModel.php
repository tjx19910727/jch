<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/1
 * Time: 10:07
 */

namespace app\AppFactory\Kernel\Model\Api;


use app\AppFactory\Kernel\Model\BaseModel;

class ApiLockStockModel extends BaseModel
{
    protected $pk = "l_id";
    protected $name = "api_lock_stock";
}