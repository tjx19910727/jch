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
            $this->app = AppFactory::machine();
            $redis = new \Redis();
            $redis->connect("127.0.0.1", "6379");
            while (true) {
                $list = $redis->lRange("machine", 0, -1);
                $num = count($list);
                if ($num > 0) {
                    $data = $redis->rPop("machine");
                    if ($data) {
                        $this->handleMachineMessage($data);
                    }
                }
                usleep(100);
            }
            $redis->close();
        } catch (\Exception $e) {
            actionException($e, 1);
        }
    }

    /**
     * 处理设备终端数据
     * @param $data
     * @return array|mixed
     */
    public function handleMachineMessage($data)
    {
        $data = json2arr($data);
        actionLog($data, '需要处理的数据');
        if ($data) {
            $result = $this->app->report->onMessage($data);
            $result = obj2arr($result);
            $result = json2arr($result);
            if ($result) {
                actionLog($result, '设备上报数据处理结果');
            }
            return $result;
        }
    }
}