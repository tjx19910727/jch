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
use app\AppFactory\Kernel\Model\Activity\Lottery\ActivityLotteryUsedModel;
use app\AppFactory\Kernel\Model\Advertisement\AdvertisementPushModel;
use app\AppFactory\Kernel\Model\Auth\AuthOrganizationModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersModel;
use app\AppFactory\Kernel\Traits\CurlTrait;
use app\AppFactory\Kernel\Util\SignUtil;
use app\AppFactory\RabbitMq\MachineConsumer;
use app\AppFactory\RabbitMq\MqProducer;
use app\BaseController;
use Mqtt\Mqtt;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Request;

class Test extends BaseController
{
    use CurlTrait;
    protected $order;

    public function testSendEmail()
    {
        $config = [
            "ao_id" => 6,
            "templateType" => "online",
            "m_id" => "1",
            "replaceData" => [
                "online" => "在线",
                "machine_id" => "test0003",
                "machine_name" => "测试3号机",
            ],
        ];
//        try {
        $app = AppFactory::notice($config);
        $result = $app->send();
        dump($result);
//        } catch (\Exception $e) {
//            dump($e->getMessage());
//        }
//        $result = $app->weChat->send();
//        dump($result);
        dump(12312);
    }

    public function testTemplate()
    {
        $data = [
            "设备编号" => [
                "value" => '{{$machineCode}}',
                "field" => 'thing01',
            ],
            "销售金额" => [
                "value" => '{{$money}}',
                "field" => 'amount03',
            ],
            "设备地址" => [
                "value" => '{{$address}}',
                "field" => 'character_string',
            ],
            "商品名称" => [
                "value" => '{{$goods_name}}',
                "field" => 'thing',
            ],
        ];

        $str = json_encode($data, 320);
        dump($str);
        $machine_code = "test0003";
        $address = "abc";
        $amount = "0.05";
        $goods_name = "测试商品";
        $str = str_replace('{{$machineCode}}', $machine_code, $str);
        $str = str_replace('{{$address}}', $address, $str);
        $str = str_replace('{{$money}}', $amount, $str);
        $str = str_replace('{{$goods_name}}', $goods_name, $str);
        $str = str_replace('{{$channelCode}}', $goods_name, $str);
        dump($str);
        dump(json2arr($str));
    }

    public function testExport()
    {
        $app = AppFactory::timeTask();
        $data = '{"export_id":45,"filename":"订单交易-20240802","title":{"machine_id":"设备编号","machine_name":"设备名称","trade_no":"订单编号","mch_no":"交易编号","goods_total_price":"商品总价","discount_price":"优惠金额","total_quantity":"总数量","total_price":"实际支付金额","pay_code":"支付操作码（付款码/支付二维码/提货码）","pay_time":"支付时间","pay_type":"支付类型","pay_method":"支付方式"},"list":[{"machine_id":"JCHH1D-003","machine_name":"JCHH1D-003嘉乐汇","trade_no":"2024080121112642830649","mch_no":"10021002408012111270685030859137","goods_total_price":"9.900","discount_price":"0.00","total_quantity":1,"total_price":"9.900","pay_code":null,"pay_time":"2024-01-08 21:11:35","pay_type":"京东收银","pay_method":"扫码支付","details":[]}],"otherData":[]}';
        $data = json2arr($data);
        $result = $app->export->makeExcel($data);
        dump($result);
    }

    public function makeSign($otherData)
    {

        $msg_id = uniqid();
        $data = [
            "msg_id" => $msg_id,
            "timestamp" => time(),
        ];
        $data = array_merge($data, $otherData);
        $signKey = MachineModel::getFieldValue(['machine_id' => $data['machine_id']], 'signKey');
//        dump(\cache($otherData['machine_id'] . ".signKey"));
        if (!$signKey) $signKey = env("api.md5Key");
        $data['sign'] = SignUtil::makeSign($data, $signKey);
        return $data;
    }

    public function testSign()
    {
        $order_id = input("order_id");
        $machine_id = input("machine_id");
        $pay_type = input("pay_type",4);
        $pay_method = input("pay_method",1);
        $mc_id = input("mc_id",43217);
        $mobile = input("mobile",15822483748);
        $cityId = input("cityId",3);
        $adults = input("adults",2);
        $quantity = input("quantity",2);
        $checkInDate = input("checkInDate",date("Y-m-d",strtotime("+1 days")));
        $checkOutDate = input("checkOutDate",date("Y-m-d",strtotime("+2 days")));
        $page = input('page',1);
        $pageNum = input("pageNum",5);
        $hotelId = input("hotelId","102903119");
        $roomId = input("roomId","99769192");
        $logId = input("logId");
        $tripData = input("tripData");
        $totalPrice = input("totalPrice");
        $pay_amount = input("pay_amount");
        $hotelFrom = input("hotelFrom",1);
        $guestNames = input("guestNames","张三,李四");
        $amount1 = input("amount1");
        $amount2 = input('amount2');
        $count = input("count",2);

//        $carList[] = [
//            "mc_id" => 30352,
//            "quantity" => 2,
//        ];
        // 提交购物车
        echo "提交购物车";
        $carList[] = [
            "mc_id" => $mc_id,
            "quantity" => 1,
        ];
        $data = [
            "machine_id" => $machine_id,
            "pay_type" => $pay_type,
            "pay_method" => $pay_method,
            "mobile" => $mobile,
//            "coupon_code" => "980429",
            "carList" => json_encode($carList, 320),
        ];
        $data = $this->makeSign($data);
        dump(json_encode($data, 320));

        // 获取酒店列表
        echo "获取酒店列表";
        $data = [
            "machine_id" => $machine_id,
            "cityId" => $cityId,
            "adults" => $adults,
            "quantity" => $quantity,
            "checkInDate" => $checkInDate,
            "checkOutDate" => $checkOutDate,
            "page" => $page,
            "pageNum" => $pageNum,
        ];
        $data = $this->makeSign($data);
        dump(json_encode($data, 320));
//        // 获取酒店详情
        echo "获取酒店详情";
        $data = [
            "machine_id" => $machine_id,
            "hotelId" => $hotelId,
        ];
        $data = $this->makeSign($data);
        dump(json_encode($data, 320));
        // 获取酒店可定售卖房型列表
        echo "获取酒店可定售卖房型列表";
        $data = [
            "machine_id" => $machine_id,
            "hotelId" => $hotelId,
            "adults" => $adults,
            "quantity" => $quantity,
            "checkInDate" => $checkInDate,
            "checkOutDate" => $checkOutDate,
        ];
        $data = $this->makeSign($data);
        dump(json_encode($data, 320));

        // 订单增加酒店内容
        echo "订单增加酒店内容";
        $data = [
            "machine_id" => $machine_id,
            "order_id" => $order_id,
            "hotelList" => [
                "hotelId" => $hotelId,
                "roomId" => $roomId,
                "num" => $quantity,
                "adults" => $adults,
                "logId" => $logId,
                "tripData" => $tripData,
                "totalPrice" => $totalPrice,
                "pay_amount" => $pay_amount,
                "hotelFrom" => $hotelFrom,
                "checkInDate" => $checkInDate,
                "checkOutDate" => $checkOutDate,
                "guestNames" => $guestNames,
                "roomPriceList" => [
                    [
                        "effectiveDate" => $checkInDate,
                        "amount" => $amount1
                    ],
                    [
                        "effectiveDate" => $checkOutDate,
                        "amount" => $amount2
                    ]
                ],
            ],
        ];
        $data = $this->makeSign($data);
        dump(json_encode($data, 320));
        // 发起支付
        echo "发起支付";
        $data = [
            "machine_id" => $machine_id,
            "order_id" => $order_id,
            "mobile" => $mobile,
        ];
        $data = $this->makeSign($data);
        dump(json_encode($data));

        // 验证房间可订
        echo "验证房间可订";
        $data = [
            "machine_id" => $machine_id,
            "hotelId" => $hotelId,
            "roomId" => $roomId,
            "count" => $count,
            "quantity" => $quantity,
            "checkInDate" => $checkInDate,
            "checkOutDate" => $checkOutDate,
            "tripData" => $tripData,
            "logId" => $logId,
            "nightlyPrice" => [
                [
                    "stayDate" => $checkInDate,
                    "salePrice" => $amount1,
                ],
                [
                    "stayDate" => $checkOutDate,
                    "salePrice" => $amount2,
                ],
            ],
        ];
        $data = $this->makeSign($data);
        dump(json_encode($data));
    }

    public function testStock()
    {
        $mc = MachineChannelModel::getList(['machine_id' => "0006", 'g_id' => "147", "status" => 1]);
        $mc = $mc->toArray();
        dump($mc);
        $totalStock = array_sum(array_column($mc, "stock"));
        dump($totalStock);

    }

    public function checkSign()
    {
        $data = '{"timestamp":"1720604606","msg_id":"bf059113-75ee-4d23-b4ff-42b9e3e35e64","machine_id":"test0003","data":"{\"msgType\":\"goodsHit\",\"g_id\":87}","sign":"f6141e8b7d927303a4f34fbfc3efb7bf"}';
        $data = json2arr($data);
        dump($data);
        $result = SignUtil::checkSign($data, 'c9243c5b87560b0308af05a204d32590');
        dump($result);
        unset($data['sign']);
        $sign = SignUtil::makeSign($data, 'c9243c5b87560b0308af05a204d32590');
        dump($sign);
    }

    public function getCheckStockQr()
    {
        $msg_id = uniqid();
        $machine_id = input("machine_id");
        $manager_id = input('manager_id');
        $data = [
            "machine_id" => $machine_id,
            "manager_id" => $manager_id,
            "timestamp" => time(),
            "msg_id" => $msg_id,
        ];
        $data['sign'] = SignUtil::makeSign($data, "1e9cf702b9a561e183e6fc450b243262");
        $config = [
            "machine_id" => $machine_id,
            "key" => env("api.md5Key"),
            "data" => $data,
        ];
        $app = AppFactory::machine($config);
        $result = $app->api->checkStockQrCode();
        return $result;
    }


    public function testUpload()
    {
        $content = [
            "msgType" => "outGoods",
            "trade_no" => "202408231430523178472",
            "main" => [
                "1" => [
                    [
                        "channel_code" => "C03",
                        "success_quantity" => 1,
                        "fail_quantity" => 0,
                        "deliver_pics" => "",
                        "out_sequence" => 1,
                    ]
                ],
            ],
        ];
        $content = json_encode($content);
        $msg_id = uniqid();
//        $signKey = "12da2ed86ebb06a199ac1d27ab062dcf";
        $data = [
            "timestamp" => time(),
            "msg_id" => $msg_id,
            "machine_id" => "test0003",
//            "mac" => "192.168.6.1",
            "data" => $content,
//            "data" => [
//                "msgType" => "updateComplete",
//                "mvp_id" => 184,
//                "status" => 2,
//            ],
        ];
        $signKey = MachineModel::getFieldValue(['machine_id' => $data['machine_id']], 'signKey');
        $data['sign'] = SignUtil::makeSign($data, $signKey);
        dump(json_encode($data));

//        $data = '{"timestamp":"1722842359","msg_id":"034be881-8c50-4aba-b8f4-cb320a37e5b3","machine_id":"0004","data":"{\"msgType\":\"outGoods\",\"trade_no\":\"2024080515164570798038\",\"main\":{\"1\":[{\"channel_code\":\"D01\",\"success_quantity\":2,\"fail_quantity\":0,\"deliver_pics\":\"/uploads/machine_0004/20240805/9c71a4607b937e39344bc5b7603a1d9d.jpg,/uploads/machine_0004/20240805/ddc2bf4a4fb22a3250de7896515a54cb.jpg\",\"out_sequence\":1}]}}","sign":"69a744b05efdb01736123c64165d8150"}';
//        $data = json2arr($data);
        $result = MqProducer::dataUpload($data);
        dump($result);
    }

    public function testTime()
    {
        $orderDate = "2012/05/01";
        dump(strtotime($orderDate));
        dump(strtotime(date("Y-m-d")));
        dump(strtotime($orderDate) != strtotime(date("Y-m-d")));
    }

    public function testGetCache()
    {
        for ($i = 0; $i <= 8; $i++) {
            $cache = cache("callback$i");
            dump($cache);
        }
    }

    public function testReturn()
    {
        $data = '{"timestamp":1723886907,"msg_id":"66c06d3bb6307","machine_id":"test0003","data":"{\"msgType\":\"outGoods\",\"trade_no\":\"754896811549786112\",\"main\":{\"1\":[{\"channel_code\":\"C02\",\"success_quantity\":2,\"fail_quantity\":0,\"deliver_pics\":\"\",\"out_sequence\":1},{\"channel_code\":\"F05\",\"success_quantity\":3,\"fail_quantity\":0,\"deliver_pics\":\"\",\"out_sequence\":1}]}}","sign":"cce36dd39767db3483669776f56b8724"}';
        $data = json2arr($data);
        dump($data);
        $config = [
            "machine_id" => $data['machine_id'],
//            "key" => env("api.md5Key"),
            "data" => $data,
        ];
//        unset($data['sign']);
//        $data['sign'] = SignUtil::makeSign($data,$config['key']);
        dump($data);
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
        $result = MqProducer::dataSend($data, $data['machine_id']);
        dump($result);
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
        $advList = AdvertisementPushModel::getList($where, 10, $field);
        dump(AdvertisementPushModel::getLS());
    }

    public function testLottery()
    {
        $msg_id = uniqid();
        $data = [
            "machine_id" => "test0001",
            "timestamp" => time(),
            "msg_id" => $msg_id,
        ];
        $key = env("api.md5Key");
        dump($key);
        $data['sign'] = SignUtil::makeSign($data, $key);
        dump($data);
        dump(json_encode($data, 320));
        $getLotteryUrl = "70cf.com/machine/receive/getLotteryList";
        $result = $this->curl_request($getLotteryUrl, "POST", $data);
        dump($result);
        $lottery = $result['data'];
        foreach ($lottery as $k => $value) {
            $msg_id = uniqid();
            $quantity = 1;
            $orderData = [
                "machine_id" => "test0001",
                "timestamp" => time(),
                "msg_id" => $msg_id,
                "pay_type" => "4",
                "pay_method" => "41",
                "total_price" => $value['price'] * $quantity,
                "total_quantity" => $quantity,
                "alc_id" => $value['config'][1]['alc_id'],
            ];
            $orderData['sign'] = SignUtil::makeSign($orderData, $key);
            dump($orderData);
            dump(json_encode($orderData, 320));
            $getLotteryOrderUrl = "70cf.com/machine/receive/getLotteryOrder";
            $orderResult = $this->curl_request($getLotteryOrderUrl, "POST", $orderData);
            dump($orderResult);
            dump(json_encode($orderResult, 320));
            SaleOrdersModel::update(['order_id' => $orderResult['data']['order_id'], 'pay_status' => 3, "pay_time" => time(), 'mch_no' => $orderResult['data']['trade_no']]);
//            $insert = [
//                "al_id" => $value['al_id'],
//                "alc_id" => $value['config'][1]['alc_id'],
//                "order_id" => $orderResult['data']['order_id'],
//                "trade_no" => $orderResult['data']['trade_no'],
//                "m_id" => $orderResult['data']['m_id'],
//                "machine_id" => $orderResult['data']['machine_id'],
//                "machine_name" => $orderResult['data']['machine_name'],
//                "price" => $value['price'],
//                "quantity" => $orderResult['data']['total_quantity'],
//                "total_price" => $orderResult['data']['total_price'],
//                "active_type" => $value['config'][1]['active_type'],
//            ];
//            ActivityLotteryUsedModel::insertOneGetId($insert);
//            dump(ActivityLotteryUsedModel::getLS());
            $luckyDraw = [
                "machine_id" => "test0001",
                "timestamp" => time(),
                "msg_id" => $msg_id,
                "order_id" => $orderResult['data']['order_id'],
            ];
            $luckyDraw['sign'] = SignUtil::makeSign($luckyDraw, $key);
            dump($luckyDraw);
            dump(json_encode($luckyDraw, 320));
            $luckyDrawUrl = "70cf.com/machine/receive/getLuckyDraw";
            $ldResult = $this->curl_request($luckyDrawUrl, "POST", $luckyDraw);
            dump($ldResult);
            dump(json_encode($ldResult, 320));
            $outGoods = [
                "machine_id" => "test0001",
                "timestamp" => time(),
                "msg_id" => $msg_id,
                "order_id" => $orderResult['data']['order_id'],
            ];
            $outGoods['sign'] = SignUtil::makeSign($outGoods, $key);
            dump($outGoods);
            dump(json_encode($outGoods, 320));
            $outGoodsUrl = "70cf.com/machine/receive/getLotteryOutGoods";
            $outResult = $this->curl_request($outGoodsUrl, "POST", $outGoods);
            dump($outResult);
            dump(json_encode($outResult, 320));
        }
    }

    public function testLotteryOutReturn()
    {
        $msg_id = uniqid();
        $outReturn = [
            "machine_id" => "test0001",
            "timestamp" => time(),
            "msg_id" => $msg_id,
            "trade_no" => "",
        ];
    }

    public function testTtMg()
    {
        $mg_id = 141;
        $app = AppFactory::timeTask();
        $app->goods->synchronizationMc($mg_id);
    }

    public function testReceiveCallback()
    {
        $ckcTime = ["24:00", "00:00"];
        $endTime = HourMinuteSec2int($ckcTime[0]);
        $startTime = HourMinuteSec2int($ckcTime[1]);
        dump($endTime);
        dump($startTime);
        dump($endTime - $startTime);
    }

    public function testCache()
    {

        $config = config("redis");
        dump($config);

    }

    public function testConnectMysql()
    {
        $connection = mysqli_connect("120.79.140.44:3306", "kiosk", "Karrie&KOS2019", "kiosk");
        $machine = $connection->query("select * from machine");
        dump($machine->fetch_all());
        if (mysqli_connect_errno()) {
            echo "连接失败：" . mysqli_connect_error();
        } else {
            echo "连接成功";
        }
        dump($connection);
    }

}