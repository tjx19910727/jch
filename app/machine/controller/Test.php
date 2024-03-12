<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/23
 * Time: 11:39
 */

namespace app\machine\controller;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Activity\ActivityGoodsModel;
use app\AppFactory\Kernel\Model\Activity\Coupon\ActivityCouponUsedModel;
use app\AppFactory\Kernel\Model\Advertisement\AdvertisementPushModel;
use app\AppFactory\Kernel\Model\Auth\AuthOrganizationModel;
use app\AppFactory\Kernel\Util\SignUtil;
use app\AppFactory\RabbitMq\MachineConsumer;
use app\AppFactory\RabbitMq\MqProducer;
use app\BaseController;
use Mqtt\Mqtt;
use think\facade\Db;
use think\facade\Queue;

class Test extends BaseController
{
    protected $order;


    public function testSign()
    {
        $msg_id = uniqid();
        $carList[] = [
            "mc_id" => 192,
            "quantity" => 2,
        ];
        $data = [
            "machine_id" => "test0001",
            "msg_id" => $msg_id,
            "timestamp" => time(),
            "pay_type" => 4,
            "pay_method" => 41,
            "coupon_code" => "511663",
            "carList" => json_encode($carList, 320),
        ];
//        $data = [
//            "order_id" => 229,
//            "timestamp" => time(),
//            "msg_id" => $msg_id,
//            "machine_id" => "test0001",
//        ];
//        $data = [
//            "machine_id" => "test0001",
//            "timestamp" => time(),
//            "msg_id" => $msg_id,
//            "delList" => "41,43",
//        ];
//        $data = [
//            "machine_id" => "test0001",
//            "timestamp" => time(),
//            "msg_id" => $msg_id,
//            "adv_id" => 13,
//            "play_time" => time(),
////            "folder" => "saleOrders",
////            "file" => file_get_contents(root_path("public/uploads/system") . "20240201/a35c07ecb552cec721f77c71fce6c5e2.jpg"),
//        ];
//        $data = [
//            "machine_id" => "test0001",
//            "timestamp" => time(),
//            "msg_id" => $msg_id,
//        ];
//        $data = [
//            "machine_id" => "test0002",
//            "timestamp" => time(),
//            "msg_id" => $msg_id,
//            "account" => "dkm3",
//            "password" => "123456",
//        ];
//        $data = [
//            "machine_id" => "test0002",
//            "timestamp" => time(),
//            "msg_id" => $msg_id,
//            "code" => "511663",
//        ];
        $data['sign'] = SignUtil::makeSign($data, "1e9cf702b9a561e183e6fc450b243262");
        dump($data);
        dump(json_encode($data, 320));
    }

    public function validateSign()
    {
        $data = '{"machine_id":"test0001", "msg_id": "8dd76668-0f7e-40ba-a451-300e129277ee", "timestamp": "1708589607516841", "mcList": "", "delList": "","sign": "b10f29791842cd915b44f5f9edbae2f8"}';
        $data = json2arr($data);
        $data['mcList'] = [];
        $data['delList'] = [];
        dump($data);
        $result = SignUtil::checkSign($data,env("api.md5Key"));
        dump($result);
    }

    public function testArrayColumn()
    {
        $list = ActivityGoodsModel::getList([],0)->toArray();
        dump($list);
        $g_ids = array_column($list,'g_id');
        dump($g_ids);
    }

    public function testUpload()
    {
        $content = [
            "msgType" => "goodsHit",
            "g_id" => "31",
        ];
        $content = json_encode($content);
        $msg_id = uniqid();
        $data = [
            "timestamp" => time(),
            "msg_id" => $msg_id,
            "machine_id" => "test0001",
            "data" => $content,
        ];
        $data['sign'] = SignUtil::makeSign($data, "1e9cf702b9a561e183e6fc450b243262");
        dump(json_encode($data));
        $this->order['machine_id'] = "test0001";

        $result = MqProducer::dataUpload($data);
        dump($result);
    }

    public function testReturn()
    {
        $data = '{"timestamp":1708941269,"msg_id":"65dc5fd539db9","machine_id":"test0001","data":"{\"msgType\":\"goodsHit\",\"g_id\":\"33\"}","sign":"790a8192fb7700a15f04f13becea20d1"}';
        $data = json2arr($data);
        $config = [
            "machine_id" => $data['machine_id'],
            "key" => env("api.md5Key"),
            "data" => $data,
        ];
        $app = AppFactory::machine($config);
        $result = $app->mq->onMessage();
        dump($result);
    }

    public function testSend()
    {
        $data = '{"timestamp":1706952289,"msg_id":"65be0661b5e38","machine_id":"test0001","data":"{\"msgType\":\"outGoods\",\"trade_no\":\"202402030910251889581\",\"main\":{\"1\":[[\"A01\",1]]}}","sign":"f69edc3f84598091e09d010246ba3764"}';
        $data = json2arr($data);
        dump($data);
        $time1 = time();
        MqProducer::dataSend($data,$data['machine_id']);
        $time2 = time();
        dump($time1);
        dump($time2);
        dump($time2 - $time1);
    }

    public function testMachineReceive()
    {
        $consumer = new MachineConsumer();
        $consumer->consumer();
    }

    public function testAdv()
    {

        $where[] = ['status', "<", 3];
        $where[] = ['start_date', '<=', time()];
        $field = "adv_title,res_id,res_title,file_path,type,duration_time,total_times,play_times,remain_times,start_date,end_date,start_time,end_time,position,screen,screen_full,status";
        $advList = AdvertisementPushModel::getList($where,10,$field);
        dump(AdvertisementPushModel::getLS());
    }
}