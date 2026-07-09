<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/23
 * Time: 10:00
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\OtaVersionTrait;
use app\AppFactory\Management\ManagementClient;

class OtaVersionClient extends ManagementClient
{
    use OtaVersionTrait;
}
