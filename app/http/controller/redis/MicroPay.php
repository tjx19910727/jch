<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/23
 * Time: 17:03
 */

namespace app\http\controller\redis;


use app\AppFactory\AppFactory;

class MicroPay
{

    public $microSec;
    /**
     * 守护进程——主动查询微信、支付宝反扫支付结果
     */
    public function queryMicroPay()
    {
        try {
            $redisExpire = env("Payment.microPayOverTime");
            $app = AppFactory::timeTask();
            $redis = new \Redis();
            $redis->connect("127.0.0.1", "6379");
            while (true) {
                $list = $redis->lRange("microPay", 0, -1);
                $num = count($list);
                if ($num > 0) {
                    $data = $redis->rPop("microPay");
                    if ($data) {
                        $data = json2arr($data);
                        if (!isset($data['query'])) $data['query'] = 1;
                        actionLog($data, '需要处理的数据');
                        if ($data && $data['time'] > time() - $redisExpire) {
                            if ($data['pay_type'] == "wx") {
                                $result = $app->wx->queryMicroPay($data['order_id']);
                            }
                            if ($data['pay_type'] == "ali") {
                                $result = $app->ali->queryMicroPay($data['order_id']);
                            }
                            $result = obj2arr($result);
                            actionLog($result, '设备上报数据处理结果');
                            // 用户支付中，重新放入队列
                            if ($result['state'] == 201) {
                                $data['query']++;
                                $redis->lPush("microPay", json_encode($data, 256 + 64));
                                $redis->expire("microPay", $redisExpire + 60);
                            }
                        }
                    }
                }
                usleep($this->microSec);
            }
            $redis->close();
            return "处理完成";
        } catch (\Exception $e) {
            actionException($e, 1);
            return $e->getMessage();
        }
    }
}