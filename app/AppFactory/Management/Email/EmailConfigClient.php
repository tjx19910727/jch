<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/4
 * Time: 10:20
 */

namespace app\AppFactory\Management\Email;


use app\AppFactory\Kernel\Traits\Email\EmailConfigTrait;
use app\AppFactory\Management\ManagementClient;

class EmailConfigClient extends ManagementClient
{
    use EmailConfigTrait;
}