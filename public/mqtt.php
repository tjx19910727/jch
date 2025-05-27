<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/29
 * Time: 16:38
 */

use Workerman\Mqtt\Client;
use Workerman\Worker;
require_once __DIR__ . '/../vendor/autoload.php';

$worker = new Worker();
$worker->onWorkerStart = function(){
    $mqtt = new Client('mqtt://broker.emqx.io:1883',['username' => "dkm","password" => "dkm123456"]);
    $mqtt->onConnect = function($mqtt) {
        $mqtt->subscribe('dataUpload');
    };
    $redis = new Redis();
    $redis->connect("127.0.0.1",'6379');
    $mqtt->onMessage = function($topic, $content) use ($redis){
        $search = array(" ", "　", "\t", "\n", "\r");
        $content = str_replace($search, '', $content);
        $redis->lPush($topic,$content);
    };
    $redis->close();
    $mqtt->connect();
};

Worker::runAll();