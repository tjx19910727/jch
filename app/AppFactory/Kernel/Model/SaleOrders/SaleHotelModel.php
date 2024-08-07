<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/3
 * Time: 12:02
 */

namespace app\AppFactory\Kernel\Model\SaleOrders;


use app\AppFactory\Kernel\Model\BaseModel;

class SaleHotelModel extends BaseModel
{
    protected $pk = "sh_id";
    protected $name = "sale_hotel";
}