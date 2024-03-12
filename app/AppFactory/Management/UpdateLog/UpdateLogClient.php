<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/1
 * Time: 17:17
 */

namespace app\AppFactory\Management\UpdateLog;


use app\AppFactory\Kernel\Traits\UpdateLog\UpdateLogTrait;
use app\AppFactory\Management\ManagementClient;

class UpdateLogClient extends ManagementClient
{
    use UpdateLogTrait;
}