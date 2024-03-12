<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/28
 * Time: 10:32
 */

namespace app\AppFactory\Kernel\Model\Advertisement;


use app\AppFactory\Kernel\Model\BaseModel;

class AdvertisementRecordModel extends BaseModel
{
    protected $pk = "ar_id";
    protected $name = "advertisement_record";
}