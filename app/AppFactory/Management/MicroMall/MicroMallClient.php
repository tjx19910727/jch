<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/9
 * Time: 14:49
 */

namespace app\AppFactory\Management\MicroMall;


use app\AppFactory\Kernel\Traits\MicroMall\MicroMallTrait;
use app\AppFactory\Management\ManagementClient;

class MicroMallClient extends ManagementClient
{
    use MicroMallTrait;
}