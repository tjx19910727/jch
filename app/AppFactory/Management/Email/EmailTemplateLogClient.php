<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/4
 * Time: 10:21
 */

namespace app\AppFactory\Management\Email;


use app\AppFactory\Kernel\Traits\Email\EmailTemplateLogTrait;
use app\AppFactory\Management\ManagementClient;

class EmailTemplateLogClient extends ManagementClient
{
    use EmailTemplateLogTrait;
}