<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/9/23
 * Time: 10:36
 */

namespace app\AppFactory\Api\Robot;


use app\AppFactory\Kernel\Traits\Machine\MachineTrait;

class RobotClient extends RobotBaseClient
{
    use MachineTrait;

    /**
     * 重新推出出货口
     * @return array|bool|string|\think\response\Json
     */
    public function re_out_port()
    {
        $return = $this->returnData(0,$this->lang("msg.0"));
        $result = $this->sendToMachine($this->params,'reOutPort');
        actionLog($result,'触发下发重推出货口结果');
        if ($result === false) {
            $return = $this->returnData(10,$this->lang("msg.10") . ":" . $this->lang("robot.machine_no_data"));
        }
        if (is_string($result)) {
            $return = $this->returnData(19,$this->lang("msg.19") . ":" .$result);
        }
        if (is_object($result)) {
            $state = obj2arr($result);
            if (isset($state['state'])) {
                if ( $state['state'] == 200) {
                    $status_code = 0;
                } else {
                    $status_code = 19;
                }
                $return = $this->returnData($status_code,$this->lang("msg.".$status_code) . ":" . $state['msg']);
            } else {
                $return = $this->returnData(19,$this->lang("msg.19"),$result);
            }
        }
        return $return;
    }
}