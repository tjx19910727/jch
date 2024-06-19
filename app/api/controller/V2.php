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
            "machine_id" => "test0003",
            "shelf_on" => 1,
        ];
        $data = [
            "auth_name" => "JCH",
            "auth_password" => "karrie@2024",
            "timestamp" => time(),
            "params" => json_encode($params,320),
        ];
        $string1 = strtoupper(md5($data['auth_password'] . $data['timestamp']));
        ksort($params);
        foreach ($params as $k => $v) {
            $signArr[] = $k . "=" . $v;
        }
        $signStr = implode(",",$signArr);
        $data['sign'] =  strtoupper(md5($string1.$signStr));
        $data['api'] = "get_inventory_list";
        unset($data['auth_password']);
        dump($data);
        dump(json_encode($data));
    }

    public function index()
    {
        $postData = input();
        $postData = json2arr($postData);
        $funcName = $postData['api'];
        $app = AppFactory::api($postData);
        if (method_exists($app->v2,$funcName)) {
            return $app->v2->$funcName();
        }
        return $app->v2->returnData(4,$app->v2->msg[4]);
    }
}  