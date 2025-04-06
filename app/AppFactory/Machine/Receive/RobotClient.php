<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/3/18
 * Time: 14:18
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\Traits\Api\ApiCallbackTrait;
use app\AppFactory\Kernel\Traits\Robot\RobotPositionTrait;

class RobotClient extends ReceiveBaseClient
{
    use RobotPositionTrait;
    use ApiCallbackTrait;

    public function position()
    {
        $robot = env("robot");
        if (isset($robot['notify_url']) && $robot['notify_url']) {
            $this->getMachineAddress();
            $message = [
                "machine_id" => $this->machine['machine_id'],
                "machine_name" => $this->machine['machine_name'],
                "address" => $this->machine['address'],
                "position" => $this->config['position']
            ];
            $insertCallback = [
                "aa_id" => 0,
                "notify_url" => $robot['notify_url'],
                "callback_type" => 9,
                "message" => json_encode($message, 320),
            ];
            $ac_id = $this->addApiCallback($insertCallback);
            actionLog($this->getLS(),'添加机器人停车位置通知记录');
            $ac = $this->getApiCallbackFind(['ac_id' => $ac_id]);
            actionLog($ac,'查询刚添加的机器人停车位置通知记录');
            if ($ac) {
                $cb = cache("callback0");
                $cb[] = $ac->toArray();
                cache("callback0",$cb,60);
            }
        }
        return $this->rSuccess();
    }
}