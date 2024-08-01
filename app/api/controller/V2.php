<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/6/18
 * Time: 15:37
 */

namespace app\api\controller;


use app\AppFactory\AppFactory;

class V2 extends Common
{

    public function testMakeSign()
    {
        $params = [
//            "machine_id" => "test0007,0010",
//            "shelf_on" => 1,
            "kiosk_id" => "test0001",
            "order_no" => "2235236256",
//            "pick_code" => "",
            "payment_method" => "wechat",
            "customer_name" => "test",
            "expire_time" => date("Y-m-d H:i:s"),
            "charge_time" => date("Y-m-d H:i:s"),
            "notify_url" => "http://www.baidu.com",
            "order_detail" => json_encode([
                "63" => [
                    "quantity" => 1,
                    "item_price" => 100,
                    "discount_amount" => 1,
                    "charge_amount" => 99,
                    "type" => "sale",
                ],
//                "87" => [
//                    "quantity" => 2,
//                    "item_price" => 50,
//                    "discount_amount" => 3,
//                    "charge_amount" => 97,
//                    "type" => "sale",
//                ],
//                "69" => [
//                    "quantity" => 1,
//                    "item_price" => 0,
//                    "discount_amount" => 0,
//                    "charge_amount" => 0,
//                    "type" => "gift",
//                ],
            ]),
        ];
//        $params = [
//            "kiosk_id" => "test0001",
//            "order_no" => "11111111",
//        ];
        $params = [
            "trade_no" => "202407291443503775978",
            "pay_status" => 1,
        ];
        $data = [
            "auth_name" => "JCH",
            "auth_password" => "karrie@2024",
            "timestamp" => time(),
            "params" => json_encode($params, 320),
        ];
        $string1 = strtoupper(md5($data['auth_password'] . $data['timestamp']));
        ksort($params);
        $signArr = [];
        foreach ($params as $k => $v) {
            $signArr[] = $k . "=" . $v;
        }
        $signStr = implode(",", $signArr);
        $data['sign'] = strtoupper(md5($string1 . $signStr));
        $data['api'] = "reserve_order";
        unset($data['auth_password']);
        dump($data);
        dump(json_encode($data));
    }

    public function index()
    {
        $postData = input();
        $postData = json2arr($postData);
        actionLog($postData,'接收到的数据');
        $funcName = $postData['api'];
        $app = AppFactory::api($postData);
        if (method_exists($app->v2, $funcName)) {
            return $app->v2->$funcName();
        }
        return $app->v2->returnData(4, lang("msg." . 4));
    }
}  