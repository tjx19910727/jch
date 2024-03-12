<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/24
 * Time: 17:32
 */

namespace app\http\controller\redis;


use app\AppFactory\AppFactory;
use app\AppFactory\Machine\Application;

class Machine
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * 设备上报数据队列，守护进程Supervisord
     * 当前方法及相关调用的方法程序，只要修改上传到服务器，需要对服务器的守护进程执行一次重加载操作
     * 服务器上执行命令
     *      supervisord -c /etc/supervisord.conf   配置文件
     *      supervisorctl startall    启动所有守护进程
     *      supervisorctl restartAll  重启所有守护进程
     *      supervisorctl start GatewayWorkerQueue 启动当前绑定的守护进程
     *      supervisorctl stop GatewayWorkerQueue  停止守护进程
     */
    public function machineMsg()
    {
        try {
            $redis = new \Redis();
            $redis->connect("127.0.0.1", "6379");
            while (true) {
                $list = $redis->lRange("dataUpload", 0, -1);
                $num = count($list);
                if ($num > 0) {
                    $data = $redis->rPop("dataUpload");
                    actionLog($data,'redis');
                    if ($data) {
                        $data = json2arr($data);
                        $config = [
                            "machine_id" => $data['machine_id'],
                            "key" => env("api.md5Key"),
                            "data" => $data,
                        ];
                        $this->app = AppFactory::machine($config);
                        $this->app->mq->onMessage();
                    }
                }
                usleep(100);
            }
            $redis->close();
        } catch (\Exception $e) {
            actionException($e, 1);
        }
    }


    public function testRedis()
    {

        $redis = new \Redis();
        $redis->connect("127.0.0.1", "6379");
        $list = $redis->lRange("dataUpload", 0, -1);
        $data = $redis->rPop("dataUpload");
        dump($list);
        dump($data);
        $redis->close();
    }
}