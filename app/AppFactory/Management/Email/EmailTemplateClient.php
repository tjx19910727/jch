<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/4
 * Time: 10:20
 */

namespace app\AppFactory\Management\Email;


use app\AppFactory\Kernel\Traits\Email\EmailTemplateTrait;
use app\AppFactory\Management\ManagementClient;

class EmailTemplateClient extends ManagementClient
{
    use EmailTemplateTrait;
}