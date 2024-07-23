<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/22
 * Time: 9:43
 */

namespace app\wx\controller;


use think\route\dispatch\Controller;

class Official extends Controller
{

    // 接收微信公众号通知
    public function receive()
    {
        $data = input();
        if ($data)
            actionLog($data, '接收到的数据');
        if (isset($data['echostr'])) {
            die($data['echostr']);
        }
        $xml = file_get_contents("php://input");
        actionLog($xml, "xml");
        $message = FromXml($xml);
        $message = json_decode(json_encode($message), true);
    }
}