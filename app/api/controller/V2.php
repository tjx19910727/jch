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
//        $params = [
////            "machine_id" => "test0007,0010",
////            "shelf_on" => 1,
//            "kiosk_id" => "test0001",
//            "order_no" => "2235236256",
////            "pick_code" => "",
//            "payment_method" => "wechat",
//            "customer_name" => "test",
//            "expire_time" => date("Y-m-d H:i:s"),
//            "charge_time" => date("Y-m-d H:i:s"),
//            "notify_url" => "http://www.baidu.com",
//            "order_detail" => json_encode([
//                "63" => [
//                    "quantity" => 1,
//                    "item_price" => 100,
//                    "discount_amount" => 1,
//                    "charge_amount" => 99,
//                    "type" => "sale",
//                ],
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
//            ]),
//        ];
//        $params = [
//            "kiosk_id" => "test0001",
//            "order_no" => "11111111",
//        ];
//        $params = [
//            "trade_no" => "202407291443503775978",
//            "pay_status" => 1,
//        ];
//        $params = [
//            "pageNum" => 15,
//            "machine_id" => "test0001",
//        ];
        $data = '{"auth_name":"Lc_test","sign":"87ED5F99A692D8F32C758B5B5CA94055","api":"get_inventory_list","params":"{\"product_id\":\"\",\"machine_id\":\"test0003\",\"shelf_on\":1}","timestamp":"1723207274"}';
        $data = json2arr($data);
//        $params = json_decode($data['params'],true);
//        $params['pageNum'] = 15;
//        $params = '{
//    "order_no": "22",
//    "expire_time": "2024-08-14 18:53:57",
//    "order_detail": "[{\"152\":{\"quantity\":1,\"type\":\"sale\",\"item_price\":8,\"discount_amount\":0,\"charge_amount\":8}},{\"168\":{\"quantity\":1,\"type\":\"sale\",\"item_price\":8,\"discount_amount\":0,\"charge_amount\":8}}]",
//    "kiosk_id": "test0001",
//    "payment_method": "wechat",
//    "charge_time": "2024-08-14 10:53:57"
//}';
//        $params = json_decode($params,true);
//        dump($params);
//        $details = json_decode($params['order_detail'],true);
//        dump($details);
//        $params = [
//            "kiosk_id" => "test0003",
//            "pageNum" => 1,
//            "page" => 2,
//        ];
//        $params = [
//            "aId" => "81",
//            "aType" => 2,
//        ];
//        $data = [
//            "auth_name" => "ctrip",
//            "auth_password" => "Karrie&C2023",
//            "timestamp" => time(),
//            "params" => json_encode($params, 320),
//        ];
        $params = [
            "pageNum" => 15,
            "page" => 1,
        ];
        $data = [
            "auth_name" => "ctrip",
            "auth_password" => "Karrie&C2023",
            "timestamp" => time(),
            "params" => json_encode($params, 320),
        ];
        dump(json_encode($data));
        dump($data['params']);
        $string1 = strtoupper(md5($data['auth_password'] . $data['timestamp']));
        dump($string1);
        ksort($params);
        $signArr = [];
        foreach ($params as $k => $v) {
            $signArr[] = $k . "=" . $v;
        }
        $signStr = $string1 . implode(",", $signArr);
        dump($signStr);
        $data['sign'] = strtoupper(md5($signStr));
        $data['api'] = "get_machines";
        unset($data['auth_password']);
        dump($data);
        dump(json_encode($data));
    }

    public function index()
    {
        if (!request()->isPost()) {
            return json(["status_code" => 98,"msg" => lang("msg." . 98)]);
        }
        if (strpos(request()->header("content-type"),'multipart/form-data') === false ) {
            return json(["status_code" => 97,"msg" => lang("msg." . 97)]);
        }
        try {
            $postData = input();
            $postData = json2arr($postData);
            actionLog($postData, '接收到的数据');
            $funcName = $postData['api'];
            $app = AppFactory::api($postData);
            if (method_exists($app->v2, $funcName)) {
                return $app->v2->$funcName();
            }
            return $app->v2->returnData(4, lang("msg." . 4));
        } catch (\Exception $e) {
            actionException($e,1);
            return  json(["status_code" => 99, "msg" => lang("msg." . 99)]);
        }
    }
}  