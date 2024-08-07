<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/3
 * Time: 12:03
 */

namespace app\AppFactory\Kernel\Model\SaleOrders;


use app\AppFactory\Kernel\Model\BaseModel;

class SaleHotelNightlyModel extends BaseModel
{
    protected $pk = "sn_id";
    protected $name = "sale_hotel_nightly";
}