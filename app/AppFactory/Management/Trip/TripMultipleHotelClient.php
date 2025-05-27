<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/10
 * Time: 10:19
 */

namespace app\AppFactory\Management\Trip;


use app\AppFactory\Kernel\Traits\Trip\TripMultipleHotelTrait;
use app\AppFactory\Management\ManagementClient;

class TripMultipleHotelClient extends ManagementClient
{
    use TripMultipleHotelTrait;
}