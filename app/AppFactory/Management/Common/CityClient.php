<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/10
 * Time: 11:14
 */

namespace app\AppFactory\Management\Common;


use app\AppFactory\Kernel\Traits\CityTrait;
use app\AppFactory\Management\ManagementClient;

class CityClient extends ManagementClient
{
    use CityTrait;
}