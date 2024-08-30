<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/26
 * Time: 17:04
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineFreeGoodsTrait;
use app\AppFactory\Management\ManagementClient;

class MachineFreeGoodsClient extends ManagementClient
{
    use MachineFreeGoodsTrait;
}