<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/2
 * Time: 16:15
 */

namespace app\AppFactory\Kernel\Model\Email;


use app\AppFactory\Kernel\Model\BaseModel;

class EmailConfigModel extends BaseModel
{
    protected $pk = "ec_id";
    protected $name = "email_config";

}