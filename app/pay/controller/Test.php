<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/23
 * Time: 14:17
 */

namespace app\pay\controller;


use app\AppFactory\Kernel\Model\Strategy\StrategyPayeeModel;
use Jd\Jd;

class Test
{

    public function pay()
    {
        $strategyPayee = StrategyPayeeModel::getFind(['sp_id' => 1]);
        $strategyPayee = obj2arr($strategyPayee);
        $strategyPayee = array_merge($strategyPayee,json2arr($strategyPayee['content']));
        dump($strategyPayee);
        $orderAmount = "0.01";
//        $params = [
//            //商户号
//            "version" => 'V4.0',
//            "customerNum" => $strategyPayee['customerNum'],
//            "requestNum" => "adsfasdfsd11111",
//            "orderAmount" => "$orderAmount",
//            "callbackUrl" => "",
//            "subOrderType" => 'NORMAL',
//            "orderType" => 'SALES', // 消费：SALES，退款：REFUND
//            "timeExpire" => date('Y-m-d H:i:s', time() + 300), // 过期时间，5分钟内
//            "bussinessType" => 'QRCODE_TRAD', // 固定值：QRCODE_TRAD
//            "payModel" => 'ONCE',  // 一单一付：ONCE，一单多付：MORE
//            "source" => 'API',
//            "extraInfo" => ""
//        ];

        $params = '{"version":"V4.0","customerNum":"10001113170548852503085","requestNum":"202402231352471777000","orderAmount":"0.01","callbackUrl":"http:\/\/api.kiosk-uat.kalos-blocks.com\/pay\/notify.jd_cashier\/orderNotify","subOrderType":"NORMAL","orderType":"SALES","timeExpire":"2024-02-23 14:31:00","bussinessType":"QRCODE_TRAD","payModel":"ONCE","source":"API","extraInfo":"{\"o_id\":342}"}';
        $params = json2arr($params);

        dump($params);
        $app = Jd::payment($strategyPayee);
        $result = $app->order->qrCodeUrl($params);
        dump($result);

    }
}