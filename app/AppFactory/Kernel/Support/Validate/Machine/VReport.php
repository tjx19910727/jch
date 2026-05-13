<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/25
 * Time: 9:04
 */

namespace app\AppFactory\Kernel\Support\Validate\Machine;


use app\AppFactory\Kernel\Support\Validate\SupportValidate;

class VReport extends SupportValidate
{
    protected $rule = [
        "machine_id" => "require",
        "msg_id" => "require|unique:machine_mq_record",
        "timestamp" => "require",
        "sign" => "require",

        "trade_no" => "require",
        "main" => "require",
        "mvp_id" => "require",
        "status" => "require",
        "g_id" => "require",

        "transaction_video" => "require",

        "field" => "require",
        "path" => "require",

        "value" => "require",

        "channel_code" => "require",
        "mc_id" => "require",

        "errorCode" => "require",
        "rsrp" => "require",
        "sinr" => "require",
    ];

    protected $message = [
        "machine_id.require" => "设备编号不能为空",
        "msg_id.require" => "消息ID不能为空",
        "msg_id.unique" => "消费ID已存在，不处理重复数据",
        "timestamp.require" => "时间戳不能为空",
        "sign.require" => "签名不能为空",

        "trade_no.require" => "订单编号不能为空",
        "main.require" => "主体数据不能为空",
        "mvp_id.require" => "更新计划ID不能为空",
        "status.require" => "更新结果不能为空",
        "g_id.require" => "商品ID不能为空",

        "transaction_video.require" => "交易视频路径不能为空",

        "field.require" => "图片字段名不能为空",
        "path.require" => "图片路径不能为空",

        "value.require" => "数值不能为空",

        "channel_code.require" => "货道编号不能为空",
        "mc_id.require" => "货道ID不能为空",

        "errorCode.require" => "错误码不能为空",
        "rsrp.require" => "信号强度不能为空",
        "sinr.require" => "信噪比不能为空",
    ];

    protected $scene = [
        "onMessage" => ["machine_id","msg_id","timestamp","sign"],

        "outGoods" => ["msgType","trade_no"],
        "heartbeat" => ["msgType"],
        "updateComplete" => ["msgType","mvp_id","status"],
        "goodsHit" => ["msgType","g_id"],

        "transactionVideo" => ["msgType","trade_no","transaction_video"],
        "img" => ["msgType","field","path"],

        "light" => ["msgType","value"],
        "volume" => ["msgType","value"],

        "channelImg" => ["msgType","channel_code","path"],

        "errorCode" => ["msgType","errorCode"],

        "uploadInfo" => ["msgType"],
        "machineServiceLog" => ["msgType"],
        "updateSimSignal" => ["msgType"],
        "currentStatus" => ["msgType","current_status"],
        
        "machineCkcOnOff" => ["msgType","ckc_status"],
       
        "doorOpen" => ["msgType"],//远程开门
        "powerWakeUp" => ["msgType"],//远程断电重启
        "initialization" => ["msgType"],//远程初始化
        "axisOffset" => ["msgType","x_axis","y_axis"],//主轴偏移
        "remoteOutGoods" => ["msgType", 'status'],//远程出货
        "remoteRemovalEnd" => ["msgType", 'mc_id', 'channel_code'],//远程下架回收结果
        "pickUpDoorOpen" => ["msgType"],//打开出料箱门回执
        "pickUpDoorClose" => ["msgType"],//关闭出料箱门回执
        "updateSimSignal" => ["msgType", "rsrp", "sinr"],//物联卡信号上报

    ];
}
