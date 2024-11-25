<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/10/14
 * Time: 17:07
 */

namespace app\machine\controller;


use app\AppFactory\Kernel\Model\Machine\MachineConfigModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersDetailsModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersModel;
use app\BaseController;
use think\View;

class Receipt extends BaseController
{
    public function receipt(View $view)
    {
        $order_id = input("order_id");
        actionLog($order_id,'订单ID');
        $order = SaleOrdersModel::getFind(['order_id' => $order_id]);
        $order = $order->toArray();
        $m = MachineModel::getFind(['m_id' => $order['m_id']],'logo,service_tel');
        $mConfig = MachineConfigModel::getFind(['m_id' => $order['m_id']],"receipt_code1,receipt_code2,receipt_code3,receipt_desc");
        $data = [
            "logo" => $m['logo'],
            'machine_name' => $order['machine_name'],
            'print_time' => date("Y-m-d H:i:s"),
            'detailsList' => SaleOrdersDetailsModel::getList(['order_id' => $order['order_id']],0,'g_name,quantity,retail_price')->toArray(),
            'total_quantity' => $order['total_quantity'],
            'discount_price' => $order['discount_price'],
            'total_price' => $order['total_price'],
            'service_tel' => $m['service_tel'],
            'receipt_code1' => $mConfig['receipt_code1'],
            'receipt_code2' => $mConfig['receipt_code2'],
            'receipt_code3' => $mConfig['receipt_code3'],
            'receipt_desc' => $mConfig['receipt_desc'],
        ];
        actionLog($data,'小票参数');
        $view->assign($data);
        $result = $view->fetch("print");
        actionLog($result,'获取小票文本');
        return returnState(200,'success',$result);
    }
}