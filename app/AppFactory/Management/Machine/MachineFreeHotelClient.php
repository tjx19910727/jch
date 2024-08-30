<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/26
 * Time: 17:03
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineFreeHotelTrait;
use app\AppFactory\Management\ManagementClient;

class MachineFreeHotelClient extends ManagementClient
{
    use MachineFreeHotelTrait;
}