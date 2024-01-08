<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/7
 * Time: 14:58
 */

namespace app\AppFactory\Management\Advertisement;


use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementPushTrait;
use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementResourceTrait;
use app\AppFactory\Management\ManagementClient;

class AdvertisementResourceClient extends ManagementClient
{
    use AdvertisementPushTrait;
    use AdvertisementResourceTrait;
}