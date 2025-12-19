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
    /**
     * 修改MQ消息记录确认已发送数据
     * @param $msg
     * @param $status
     */
    public function confirmSend($msg,$status)
    {
        $this->updateMachineMqRecord(["status" => $status],['msg_id' => $msg['msg_id'],'machine_id' => $msg['machine_id']]);
    }


    /**
     * 主体控制 休眠：sleep, 唤醒：wakeUp, 重启：reboot, 关机：shutdown, 软件升级：update
     * @param string $msgType
     * @param string $time_point  生效的时间点
     * @return array|string
     */
    /**
     * 交易视频 transactionVideo
     * @param $msgType
     * @param $trade_no
     * @return array|string
     */
    /**
     * 下发获取首页截屏、设备内部照片、出货箱照片  img
     * @param $field
     * @return array|string
     */
    /**
     * 下发设置灯光亮度   light
     * @param $light
     * @return array|string
     */
    /**
     * 下发设置音量   volume
     * @param $volume
     * @return array|string
     */
    /**
     * 下发货道槽位拍照   channelImg
     * @param string $channel_code 货道号
     * @return array|string
     */
    /**
     * 触发广告更新   updateAd
     * @return array|string
     */
    /**
     * 触发设备商品更新  updateMg
     * @param $mg_id
     * @return array|string
     */
    /**
     * 订单支付成功下发  paySuccess
     * @param $trade_no
     * @return array|string
     */
    /**
     * 触发设备货道更新 updateMc
     * @param int $mc_id
     * @return array|string
     */
    /**
     * 触发设备更新  updateMachine
     * @return array|string
     */
    /**
     * 触发设备配置更新 updateMachineConfig
     * @return array|string
     */
    /**
     * 下发设备营业状态 machineCkcOnOff
     * @return array|string
     */
    /**
     * 触发设备营业配置更新  updateMachineOnOff
     * @return array|string
     */
    /**
     * 触发设备更新系统配置信息  updateSystemInfo
     * @return array|string
     */
    /**
     * 触发设备更新软件版本 updateVersionPlan
     * @return array|string
     */
    /**
     * 触发设备首页模板界面更新  updateMachineView
     * @return array|string
     */
    /**
     * 触发设备更新商品库  updateGoods
     * @param int $g_id
     * @return array|string
     */

    /**
     * 通知设备退出H5页面  logoutH5
     * @return array|string
     */

    /**
     * 主动发送至MQ
     * @param $msgType
     * @param array $otherData
     * @return array|string
     */
    public function sendMq($msgType,$otherData = [])
    {
        $data = ['msgType' => $msgType];
        if ($otherData) $data = array_merge($data,$otherData);
        return $this->dataSendRabbitMQ($data);
    }



}