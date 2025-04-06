<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/11
 * Time: 15:13
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineSaleTrait;
use app\AppFactory\Management\ManagementClient;

class MachineSaleClient extends ManagementClient
{
    use MachineSaleTrait;
}