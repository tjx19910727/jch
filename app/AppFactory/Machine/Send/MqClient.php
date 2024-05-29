<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/2/22
 * Time: 11:52
 */

namespace app\AppFactory\Machine\Send;


class MqClient extends SendBaseClient
{
    public function confirmSend($msg,$status)
    {
        $this->updateMachineMqRecord(["status" => $status],['msg_id' => $msg['msg_id'],'machine_id' => $msg['machine_id']]);
    }

    /**
     * 主体控制
     * @param string $msgType  1. 休眠：sleep, 2. 唤醒：wakeUp, 3. 重启：reboot, 4. 关机：shutdown, 5. 软件升级：update
     * @param string $time_point  生效的时间点
     * @return array|string
     */
    public function main_control($msgType,$time_point = "")
    {
        if ($time_point && is_string($time_point)) $time_point = strtotime($time_point);
        $data = [
            "msgType" => $msgType,
            "time_point" => ($time_point ? $time_point : time()),
        ];
        return $this->dataSendRabbitMQ($data);
    }

    /**
     * 交易视频
     * @param $msgType
     * @param $trade_no
     * @return array|string
     */
    public function getTransactionVideo($trade_no)
    {
        $data = [
            "msgType" => "transactionVideo",
            "trade_no" => $trade_no,
        ];
        return $this->dataSendRabbitMQ($data);
    }

    /**
     * 下发获取首页截屏、设备内部照片、出货箱照片
     * @param $field
     * @return array|string
     */
    public function getImg($field)
    {
        $data = [
            "msgType" => "img",
            "field" => $field,
        ];
        return $this->dataSendRabbitMQ($data);
    }

    /**
     * 下发设置灯光亮度
     * @param $light
     * @return array|string
     */
    public function setLight($light)
    {
        $data = [
            "msgType" => "light",
            "value" => $light,
        ];
        return $this->dataSendRabbitMQ($data);
    }

    /**
     * 下发设置音量
     * @param $volume
     * @return array|string
     */
    public function setVolume($volume)
    {
        $data = [
            "msgType" => "volume",
            "value" => $volume,
        ];
        return $this->dataSendRabbitMQ($data);
    }

    /**
     * 下发货道槽位拍照
     * @param string $channel_code 货道号
     * @return array|string
     */
    public function getChannelImg($channel_code)
    {
        $data = [
            "msgType" => "channelImg",
            "channel_code" => $channel_code,
        ];
        return $this->dataSendRabbitMQ($data);
    }

    /**
     * 触发广告更新
     * @return array|string
     */
    public function triggerUpdateAD()
    {
        $data = [
            "msgType" => "updateAD",
        ];
        return $this->dataSendRabbitMQ($data);
    }

    /**
     * 触发设备商品更新
     * @param $mg_id
     * @return array|string
     */
    public function triggerUpdateMg($mg_id)
    {
        $data = [
            "msgType" => "updateMg",
            "mg_id" => $mg_id,
        ];
        return $this->dataSendRabbitMQ($data);
    }

    /**
     * 订单支付成功下发
     * @param $trade_no
     * @return array|string
     */
    public function paySuccess($trade_no)
    {
        $data = [
            "msgType" => "paySuccess",
            "trade_no" => $trade_no,
        ];
        return $this->dataSendRabbitMQ($data);
    }

    /**
     * 触发设备货道更新
     * @param int $mc_id
     * @return array|string
     */
    public function triggerUpdateMc($mc_id)
    {
        $data = [
            "msgType" => "updateMc",
            "mc_id" => $mc_id,
        ];
        return $this->dataSendRabbitMQ($data);
    }

    /**
     * 触发设备更新
     * @return array|string
     */
    public function triggerUpdateMachine()
    {
        $data = [
            "msgType" => "updateMachine",
        ];
        return $this->dataSendRabbitMQ($data);
    }

    /**
     * 触发设备配置更新
     * @return array|string
     */
    public function triggerUpdateMachineConfig()
    {
        $data = [
            "msgType" => "updateMachineConfig",
        ];
        return $this->dataSendRabbitMQ($data);
    }

    /**
     * 触发设备营业配置更新
     * @return array|string
     */
    public function triggerUpdateMachineOnOff()
    {
        $data = [
            "msgType" => "updateMachineOnOff",
        ];
        return $this->dataSendRabbitMQ($data);
    }

    /**
     * 触发设备更新系统配置信息
     * @return array|string
     */
    public function triggerUpdateSystemInfo()
    {
        $data = [
            "msgType" => "updateSystemInfo",
        ];
        return $this->dataSendRabbitMQ($data);
    }
}