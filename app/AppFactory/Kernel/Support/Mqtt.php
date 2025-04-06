<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/31
 * Time: 11:07
 */

namespace app\AppFactory\Kernel\Support;


class Mqtt
{
    public static function send($topic,$content,$client_id)
    {
        is_string($content) ? : $content = json_encode($content);
        $server = env("mqtt.server");
        $port = env("mqtt.port");
        $username = env("mqtt.username");
        $password = env("mqtt.password");
        $mqtt = new \Mqtt\Mqtt($server, $port, $client_id); //实例化MQTT类
        if ($mqtt->connect(true, NULL, $username, $password)) {
            actionLog(['topic' => $topic,'content' => $content],'mqtt下发');
            $mqtt->publish($topic, $content);
            $mqtt->close();
        } else {
            actionLog("MQTT连接失败");
        }
    }
}