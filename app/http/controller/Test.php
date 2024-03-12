<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/23
 * Time: 16:20
 */

namespace app\http\controller;


use app\AppFactory\AppFactory;
use app\BaseController;

class Test extends BaseController
{
    public function testMachineOnline()
    {
        $app = AppFactory::timeTask();
        $app->machine->countOnline();
    }

    public function testMachineSale()
    {
        $app = AppFactory::timeTask();
        $app->machine->collectDailySalesData();
    }

    public function testGoodsSale()
    {
        $app = AppFactory::timeTask();
        $app->goods->collectDailySalesData();
    }

    public function testStatisticalSaleAmount()
    {
        $app = AppFactory::timeTask();
        $app->machine->statisticalSaleAmount();
    }

    public function testGoodsStatisticalSaleAmount()
    {
        $app = AppFactory::timeTask();
        $app->goods->statisticalSaleAmount();
    }

    public function testOauth2()
    {

    }
}