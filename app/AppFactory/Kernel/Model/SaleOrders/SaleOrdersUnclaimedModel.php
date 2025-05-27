<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/22
 * Time: 15:21
 */

namespace app\AppFactory\Kernel\Model\SaleOrders;


use app\AppFactory\Kernel\Model\BaseModel;

class SaleOrdersUnclaimedModel extends BaseModel
{
    protected $pk = "su_id";
    protected $name = "sale_orders_unclaimed";
}