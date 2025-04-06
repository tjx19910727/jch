<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/28
 * Time: 16:03
 */

namespace app\AppFactory\Kernel\Traits\GatewayWorker;


use GatewayWorker\Lib\Gateway;

trait GatewayWorkerTrait
{
    public function register()
    {
        Gateway::$registerAddress = "127.0.0.1:1238";
    }

    public function sendGateway($uid, $data,$msgType = "")
    {
        Gateway::$registerAddress = "127.0.0.1:1238";
        if (is_object($data) && method_exists($data,"getData")) $data = $data->getData();
        $send = json2arr($data);
        if ($msgType) {
            $send['msgType'] = $msgType;
        }
        actionLog(['uid' => $uid,"send" => $send],"发送的数据");
        $send = json_encode($send,JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE);
        Gateway::sendToUid($uid, $send);
        return true;
    }

    public function sendGatewayGroup($group,$data,$msgType = "")
    {
        Gateway::$registerAddress = "127.0.0.1:1238";
        if (is_object($data) && method_exists($data,'getData')) $data = $data->getData();
        $send = json2arr($data);
        if ($msgType) {
            $send['msgType'] = $msgType;
        }
        actionLog(['group' => $group,"send" => $send],"发送的数据");
        $send = json_encode($send,JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE);
        $client_id = $this->client_id ?? null;
        Gateway::sendToGroup($group, $send,$client_id);
        return true;
    }

    public function sendGatewayClientID($clientID,$data,$msgType = '')
    {
        Gateway::$registerAddress = "127.0.0.1:1238";
        if (is_object($data) && method_exists($data,'getData')) $data = $data->getData();
        $send = json2arr($data);
        if ($msgType) {
            $send['msgType'] = $msgType;
        }
        actionLog(['client_id' => $clientID,"send" => $send],"发送的数据");
        $send = json_encode($send,JSON_UNESCAPED_SLASHES+JSON_UNESCAPED_UNICODE);
        Gateway::sendToClient($clientID, $send);
        return true;
    }

    /**
     * 获取连接ID
     * @param $uid
     * @return array
     */
    public function getClientIDByUid($uid)
    {
        Gateway::$registerAddress = "127.0.0.1:1238";
        $this->client_id = Gateway::getClientIdByUid($uid);
        return $this->client_id;
    }
}