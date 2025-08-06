<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/29
 * Time: 14:35
 */

namespace app\AppFactory\TimeTask;


use app\AppFactory\Kernel\BaseClient;
use app\AppFactory\Kernel\Traits\Config\ConfigTrait;
use app\AppFactory\Kernel\Traits\Send\ToManagerTrait;

class TimeTaskBase extends BaseClient
{
    use ConfigTrait;
    use ToManagerTrait;
}