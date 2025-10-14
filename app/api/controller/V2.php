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
        $data = '{"auth_name":"Lc_test","sign":"87ED5F99A692D8F32C758B5B5CA94055","api":"get_inventory_list","params":"{\"product_id\":\"\",\"machine_id\":\"test0003\",\"shelf_on\":1}","timestamp":"1723207274"}';
        $data = json2arr($data);
        $machine_id = input("machine_id", "test0001");
        $goods_id = input("goods_id");
        $type = input("type");
        $page = input("page", 1);
        $pageNum = input("pageNum", 20);
        $apiName = input("apiName");
        if ($type == 1) {
            // 预订商品
            $params = [
                "kiosk_id" => $machine_id,
                "order_no" => "20241214101258" . random_int(000000, 999999),
                "payment_method" => "wechat",
                "expire_time" => date("Y-m-d H:i:s", strtotime("+1 days")),
                "charge_time" => date("Y-m-d H:i:s"),
                "order_detail" => json_encode([
                    $goods_id => [
                        "quantity" => 1,
                        "item_price" => 3,
                        "discount_amount" => 0,
                        "charge_amount" => 3,
                        "type" => "sale",
                    ],
                ], 320),
            ];
            $apiName ?: $apiName = "reserve_order";
        }
        if ($type == 2) {
            // 使用提货码
            $params = [
                "pick_code" => input("pick_code", 000000),
                "machine_id" => $machine_id,
            ];
            $apiName ?: $apiName = "use_pick_code";
        }
        if ($type == 3) {
            $apiName ?: $apiName = "get_inventory_list";
            $params = [
                "machine_id" => $machine_id,
                "shelf_on" => 1,
                "pageNum" => 15,
                "page" => 1,
            ];
        }
        if ($type == 4) {
            $apiName ?: $apiName = "get_machines";
            $params = [
                "pageNum" => $pageNum,
                "page" => $page
            ];
        }
        if ($type == 11) {
            $apiName ?: $apiName = "get_goods_category";
            $params = [
                "pageNum" => $pageNum,
                "page" => $page
            ];
        }
        if ($type == 12) {
            $params = [
                "machine_id" => $machine_id,
            ];
        }
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
        $data['api'] = $apiName;
        unset($data['auth_password']);
        dump($data);
        dump(json_encode($data));
        die();
    }

    /**
     * 通用接口
     * @return array|\think\response\Json
     */
    public function index()
    {
        if (!request()->isPost()) {
            return json(["status_code" => 98, "msg" => lang("msg." . 98)]);
        }
        if (strpos(request()->header("content-type"), 'multipart/form-data') === false) {
            return json(["status_code" => 97, "msg" => lang("msg." . 97)]);
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
            actionException($e, 1);
            return json(["status_code" => 99, "msg" => lang("msg." . 99) . $e->getMessage()]);
        }
    }

    /**
     * 机器人平台接口
     * @return array|\think\response\Json
     */
    public function robot()
    {
        if (!request()->isPost()) {
            return json(["status_code" => 98, "msg" => lang("msg." . 98)]);
        }
        if (strpos(request()->header("content-type"), 'multipart/form-data') === false) {
            return json(["status_code" => 97, "msg" => lang("msg." . 97)]);
        }
        try {
            $postData = input();
            $postData = json2arr($postData);
            actionLog($postData, '接收到的数据');
            $funcName = $postData['api'];
            $robot = AppFactory::api($postData)->robot;
            if (method_exists($robot, $funcName)) {
                return $robot->$funcName();
            }
            return $robot->r(4, lang("msg." . 4));
        } catch (\Exception $e) {
            actionException($e, 1);
            return json(["status_code" => 99, "msg" => lang("msg." . 99) . $e->getMessage()]);
        }
    }
}  