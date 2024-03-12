<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/2
 * Time: 16:26
 */

namespace app\AppFactory\TimeTask\Goods;


use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\TimeTask\TimeTaskBase;

class GoodsClient extends TimeTaskBase
{
    use GoodsTrait,GoodsCategoryTrait;
    use SaleOrdersTrait;


}