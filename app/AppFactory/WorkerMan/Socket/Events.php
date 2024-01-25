<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */

//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * @param int $client_id 连接id
     * @throws Exception
     */
    public static function onConnect($client_id)
    {
        // 向当前client_id发送数据
        Gateway::sendToClient($client_id, "Connection SUCCESS $client_id\n");
        // 向所有人发送
//        Gateway::sendToAll("$client_id connect success\n");
//        Gateway::bindUid($client_id);
    }


    // 不需要处理的信息类型
    protected static $noHandleMsgType = [];

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
//        try {
            $search = array(" ", "　", "\t", "\n", "\r");
            $message = str_replace($search, '', $message);
            Gateway::sendToCurrentClient($message);
            $message = json_decode($message, true);
            if ($message) {
                // 设备终端信息
                if ($message['msgType'] == "login") {
                    Gateway::bindUid($client_id, $message['machine_id']);
                    Gateway::joinGroup($client_id, $message['machine_id']);
                    $_SESSION[$client_id] = $message['machine_id'];
                }
                $key = "machine";
                $data = json_encode(['client_id' => $client_id, 'message' => $message]);
                // 排除不需要处理的数据
                if (!in_array($message['msgType'], self::$noHandleMsgType)) {
                    $redis = new Redis();
                    $redis->connect("127.0.0.1", "6379");
                    $redis->lPush($key, $data);
                    $redis->close();
                }
                $data = json_encode(["state" => 1, 'msg' => "Received successfully! ", "msgType" => $message['msgType']], 256 + 64);
                Gateway::sendToCurrentClient($data);
            }
//        } catch (Exception $e) {
//            dump($e->getMessage());
//            Gateway::sendToCurrentClient($e->getMessage());
//        }
    }

    /**
     * 当用户断开连接时触发
     * @param string $client_id 连接ID
     * @throws Exception
     */
    public static function onClose($client_id)
    {
        $uid = $_SESSION[$client_id] ?? '';
        $message['message'] = ['msgType' => "onClose"];
        $message['client_id'] = $client_id;
        if ($uid) {
            Gateway::sendToUid($uid, "$client_id logout");
            Gateway::sendToGroup($uid,"$client_id logout");
            $message['terminal_no'] = $uid;
        }
        $redis = new Redis();
        $redis->connect("127.0.0.1", "6379");
        $redis->lPush("onMessage", json_encode($message, 256 + 64));
        $redis->close();
    }
}