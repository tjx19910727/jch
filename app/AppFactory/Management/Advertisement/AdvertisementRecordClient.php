<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/10
 * Time: 11:11
 */

namespace app\AppFactory\Management\Advertisement;


use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementRecordTrait;
use app\AppFactory\Management\ManagementClient;

class AdvertisementRecordClient extends ManagementClient
{
    use AdvertisementRecordTrait;
}