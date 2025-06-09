<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/22
 * Time: 19:58
 */

namespace app\AppFactory\Pay;


use app\AppFactory\Kernel\BaseClient;
use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\CommonTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsMultipleGoodsTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsMultipleTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;

class PayBaseClient extends BaseClient
{
    use SaleOrdersTrait,SaleHotelTrait;
    use GoodsMultipleGoodsTrait,GoodsMultipleTrait;
    use MachineTrait;
    use CommonTrait;

    public $data;
    public $order;
    public $payType;
    public $machine;
    public $refundTradeNo;
    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->data = $this->config['data'] ?? [];
    }

}