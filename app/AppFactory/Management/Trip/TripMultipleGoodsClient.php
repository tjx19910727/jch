<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/10
 * Time: 10:18
 */

namespace app\AppFactory\Management\Trip;


use app\AppFactory\Kernel\Traits\Trip\TripMultipleGoodsTrait;
use app\AppFactory\Management\ManagementClient;

class TripMultipleGoodsClient extends ManagementClient
{
    use TripMultipleGoodsTrait;
}