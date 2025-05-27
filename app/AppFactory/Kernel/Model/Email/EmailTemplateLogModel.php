<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/2
 * Time: 16:16
 */

namespace app\AppFactory\Kernel\Model\Email;


use app\AppFactory\Kernel\Model\BaseModel;

class EmailTemplateLogModel extends BaseModel
{
    protected $pk = "el_id";
    protected $name = "email_template_log";
}