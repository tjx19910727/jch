<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/23
 * Time: 11:39
 */

namespace app\machine\controller;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Action\ActionVideoModel;
use app\AppFactory\Kernel\Model\Activity\ActivityGoodsModel;
use app\AppFactory\Kernel\Model\Activity\Coupon\ActivityCouponUsedModel;
use app\AppFactory\Kernel\Model\Activity\Lottery\ActivityLotteryUsedModel;
use app\AppFactory\Kernel\Model\Advertisement\AdvertisementPushModel;
use app\AppFactory\Kernel\Model\Auth\AuthOrganizationModel;
use app\AppFactory\Kernel\Model\Goods\GoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersModel;
use app\AppFactory\Kernel\Traits\CurlTrait;
use app\AppFactory\Kernel\Util\SignUtil;
use app\AppFactory\RabbitMq\MachineConsumer;
use app\AppFactory\RabbitMq\MqProducer;
use app\BaseController;
use Mqtt\Mqtt;
use think\Exception;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Queue;
use think\facade\Request;

class Test extends BaseController
{
    use CurlTrait;
    protected $order;
    public $machine;
    public $mqQueue;

    public function testOutPort()
    {
        dump(\request()->action());
        die();
        $postData = input();
//        $dc = [
//            "channel_code" => $v['channel_code'],
//            "quantity" => $v['quantity'],
//            "is_gift" => $is_gift ?? 2,
//            "out_port" => $out_port ?? 1,
//        ];
        $outArr[$postData['channel_position'] ?? 1] = $postData["dc"];
        $content = [
            "msgType" => "outGoods",
            "trade_no" => "test" . date("YmdHis") . random_int(100000,999999),
            "outGoods" => $outArr,
        ];
        $content = json_encode($content);
        $data = [
            "timestamp" => time(),
            "msg_id" => uniqid(),
            "machine_id" => $postData['machine_id'],
            "data" => $content,
        ];
        $machine = MachineModel::getFind(['machine_id' => $postData['machine_id']],'machine_id,mac_address,signKey');
        $data['sign'] = SignUtil::makeSign($data, $machine['signKey']);
        dump($data);
        $mqQueue = $machine['machine_id'] . "_" . str_replace(":","_",$machine['mac_address']);
        dump($mqQueue);
        $result = MqProducer::dataSend($data,$mqQueue);
        dump($result);
    }

    public function testAfterRead()
    {
        $path = rtrim(public_path("/uploads/adv/20241116/ed1f2992640d9e38ea98b2083593e26d.mp4"),'/');
        dump($path);
        dump(file_exists($path));
        dump(is_file($path));
        $video = ActionVideoModel::getList([],0,'*','id desc');
        dump($video);
    }
    public function testWhere()
    {
        $contentGid = [156,158,607];
        $quantity = 1;
        $this->machine['machine_id'] = "JCHH2D-030";
        $whereNoGid = function ($query) use ($contentGid,$quantity)  {
            $query->where("machine_id = '" . $this->machine['machine_id'] . "' and (status <> 1 or stock < $quantity) AND g_id in (" . implode(",",$contentGid) . ")");
        };
        $result = MachineChannelModel::getColumn($whereNoGid,'g_id');
        dump(MachineChannelModel::getLS());
        dump($result);
    }

    public function testArr()
    {
        dump("12+++" >= 12);
        $this->machine['machine_id'] = "test0001";
        $this->machine['version'] = "0.2.12+";
        $this->machine['mac_address'] = "04:2B:58:12:15:00";
        $this->mqQueue = $this->machine['machine_id'];
        if (isset($this->machine['version']) && $this->machine['version']) {
            $versionArr = explode(".", $this->machine['version']);
            dump($versionArr);
            // 版本号小于0.2.12时，采用旧的MQ队列，大于等于0.2.12时，MQ队列名称增加MAC地址标示
            if (isset($versionArr[0]) && $versionArr[0] == 0 && isset($versionArr[1]) && $versionArr[1] >= 2 && isset($versionArr[2]) &&  $versionArr[2] >= 12) {
                $this->mqQueue = $this->machine['machine_id'] . "_" . str_replace(":","_",$this->machine['mac_address']);
            }
        }
        dump($this->mqQueue);
        die();
        $data = [
            ["g_id" => 5],
            ["g_id" => 6],
            ["g_id" => 7],
            ["g_id" => 4],
        ];
        $gIds = array_column($data, "g_id");
        dump($gIds);
    }

    public function testBc()
    {
        $value = '{"sod_id":5246,"discount_price":"0.000","total_sod_price":1,"retail_price":1,"quantity":1,"g_id":156,"cost_price":0}';
        $value = json2arr($value);
        $this->order = '{"order_id":3689,"trade_no":"2024090510173754820433","out_trade_no":null,"mch_no":null,"user_id":0,"user_name":null,"mobile":"","m_id":54,"machine_name":"JCHS2D-0022","machine_id":"0022","ao_id":1,"order_type":1,"fd_id":0,"coupon_id":0,"apc_id":0,"lottery_id":0,"pay_status":1,"pay_type":4,"pay_method":1,"pay_time":null,"pay_code":null,"refund_status":1,"refund_amount":"0.000","refund_quantity":0,"discount_price":"0.50","cost_price":"25.000","market_price":"66.000","retail_price":"2.500","total_price":"2.000","total_quantity":2,"out_status":1,"out_time":null,"goods_type":1,"has_hotel":2,"transaction_video":null,"remark":null,"manager_id":0,"create_date":1725465600,"create_time":1725502657,"update_time":1725502657,"sp_id":0}';
        $this->order = json2arr($this->order);
        dump($this->order);
        dump($value);
        $discount = bcmul($this->order['discount_price'], bcdiv($value['total_sod_price'], $this->order['total_price'], 2), 3);
        dump($discount);
    }

    public function testSendEmail()
    {
        $config = [
            "ao_id" => 1,
            "templateType" => "understock",
            "m_id" => "3",
            "replaceData" => [
                "machine_name" => "测试3号机",
                "channel_code" => "C03",
            ],
            "sendType" => 1,
        ];
//        try {
        $app = AppFactory::notice($config);
        $result = $app->weChat->send();
        dump($result);
        die();
//        } catch (\Exception $e) {
//            dump($e->getMessage());
//        }
//        $result = $app->weChat->send();
//        dump($result);
        $config['sendType'] = 2;
        $app = AppFactory::notice($config);
        $result = $app->email->send();
        dump($result);
//        dump(12312);
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
        $data = input();
        $data = json2arr($data);
        $data = $this->makeSign($data);
        echo '<br>',json_encode($data,320),'<br>','<br>';
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
        $data = input();
        $data = json2arr($data);
//        $content = [
//            "msgType" => "outGoods",
//            "trade_no" => "202408231430523178472",
//            "main" => [
//                "1" => [
//                    [
//                        "channel_code" => "C03",
//                        "success_quantity" => 1,
//                        "fail_quantity" => 0,
//                        "deliver_pics" => "",
//                        "out_sequence" => 1,
//                    ]
//                ],
//            ],
//        ];
//        $content = json_encode($content);
        $msg_id = uniqid();
////        $signKey = "12da2ed86ebb06a199ac1d27ab062dcf";
//        $data = [
//            "timestamp" => time(),
//            "msg_id" => $msg_id,
//            "machine_id" => "test0003",
////            "mac" => "192.168.6.1",
//            "data" => $content,
////            "data" => [
////                "msgType" => "updateComplete",
////                "mvp_id" => 184,
////                "status" => 2,
////            ],
//        ];
//        $signKey = MachineModel::getFieldValue(['machine_id' => $data['machine_id']], 'signKey');
//        $data['sign'] = SignUtil::makeSign($data, $signKey);
//        dump(json_encode($data));

        if (!$data) {
            $where['machine_id'] = input("machine_id");
            $machine = MachineModel::getFind($where);
            $content = input("content");
            $data = [
                "timestamp" => time(),
                "msg_id" => $msg_id,
                "machine_id" => $machine['machine_id'],
                "data" => $content,
                "mac" => $machine['mac'],
            ];
            $data['sign'] = SignUtil::makeSign($data, $machine['signKey']);
//        $data = '{"timestamp":"1732006384","msg_id":"fd591f1c-7718-4069-a290-52d084930c82","machine_id":"JCHH2D-027","data":"{\"msgType\":\"light\",\"value\":100}","mac":"04:2B:58:12:16:3A","sign":"58457494a8e727d42be089f184c333f4"}';
//        $data = json2arr($data);
        }
        dump($data);
//        $data = [
//            "timestamp" => time(),
//            "msg_id" => $msg_id,
//            "machine_id" => "test0003",
//            "mac" => "00:0C:29:74:77:66",
//        ];
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
        $data = input();
        $data = json2arr($data);
        if (!isset($data['mac'])) $data['mac'] = MachineModel::getFieldValue(['machine_id' => $data['machine_id']],'mac_address');
        $config = [
            "machine_id" => $data['machine_id'],
//            "key" => env("api.md5Key"),
            "data" => $data,
            "mac" => $data['mac'],
        ];
        dump($config);
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

    public function testMoreSend()
    {
        $machine_id = input("machine_id");
        $num = input("num");
        $signKey = MachineModel::getFieldValue(['machine_id' => $machine_id], 'signKey');
        $time1 = time();
        for ($i=0; $i < $num;$i++) {
            $data = [
                "timestamp" => time(),
                "msg_id" => uniqid(),
                "machine_id" => $machine_id,
                "data" => [
                    "msgType" => "updateAD",
                ]
            ];
            $data['sign'] = SignUtil::makeSign($data, $signKey);
            $result = MqProducer::dataSend($data,$data['machine_id']);
            $temp[] = ['data' => $data,'result' => $result];
        }
        $time2 = time();
        dump($time2 - $time1);
        dump($temp);

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
        $sale = "422.15";
        $refund = "20.1";
        $total = $sale - $refund;
        dump($total);

    }

    public function testConnectMysql()
    {
        $connection = mysqli_connect("172.16.0.80:3306", "kiosk", "Karrie*KOS2019", "kiosk");
        $machine = $connection->query("select * from machine");
        dump($machine->fetch_all());
        if (mysqli_connect_errno()) {
            echo "连接失败：" . mysqli_connect_error();
        } else {
            echo "连接成功";
        }
        dump($connection);
    }


    public function testGm()
    {
        $carList = [
            [
                "gmg_id" => 26,
                "mc_id" => 0,
                "g_id" => 984,
                "quantity" => 2,
                "sod_price" => 2.2
            ],
            [
                "gmg_id" => 27,
                "mc_id" => 43198,
                "g_id" => 548,
                "quantity" => 2,
                "sod_price" => 17.82
            ],
        ];
        $roomList = [
            [
                "effectiveDate" => "2024-12-01",
                "amount" => 21000,
            ],
            [
                "effectiveDate" => "2024-12-02",
                "amount" => 21000,
            ],
        ];
        $hotel = [
            "gmg_id" => 24,
            "totalPrice" => 40000,
            "hotelId" => "",
            "roomId" => "",
            "num" => 2,
            "adults" => 4,
            "pay_amount" => 42000,
            "checkInDate" => "2024-12-01",
            "checkOutDate" => "2024-12-03",
            "guestNames" => "赵大，张三，李四，王五",
            "roomPriceList" => $roomList,
        ];
        $data = [
            "machine_id" => "test0003",
            "total_price" => 440.02,
            "pay_type" => 5,
            "pay_method" => 1,
            "mobile" => "15822483748",
            "gm_id" => 17,
            "carList" => json_encode($carList, 320),
            "hotel" => json_encode($hotel, 320),
        ];
        $data = $this->makeSign($data);
        echo json_encode($data,320),'<br>';
    }

    public function testScanDir()
    {
//        $dir = root_path("public");
        $dir = "E:\project\project-70-cf.com\public";
        dump($dir);
        $match = "/\.php$/i";
        $list = $this->scanDir($dir, $match);
        dump($list);
        $whiteList = [
            "E:\project\project-70-cf.com\public/index.php",
            "E:\project\project-70-cf.com\public/router.php",
            "E:\project\project-70-cf.com\public/mqtt.php"
        ];
        foreach ($list as $key => $value) {
            if (!in_array($value, $whiteList)) {
                dump($value);
                unlink($value);
            }
        }
    }

    /**
     * Notes: 扫描目录，查询正则匹配名称的文件
     * User: HappyWinter
     * Date: 2024/11/13
     * Time: 14:15
     * @param string $dir 指定目录，结尾不带/或\
     * @param string $match 正则表达式，默认值：匹配后缀名为.php的文件返回
     * @return array               符合条件的文件列表返回
     */
    public function scanDir($dir, $match = "/\.php$/i"): array
    {
        $ff = scandir($dir);
        $fileList = [];
        foreach ($ff as $key => $value) {
            if ($value == "." || $value == "..") continue;
            if (is_dir($dir . "/" . $value)) {
                $fileList = array_merge($fileList, $this->scanDir($dir . "/" . $value, $match));
            } else {
                if (preg_match($match, $value)) {
                    $fileList[] = $dir . "/" . $value;
                }
            }
        }
        return $fileList;
    }

    public function testShowImg()
    {
        $name = "8d57499656f58704bfbcabd4cc39e90a";
        echo "<img src='" . url("machine/test/showImg", ['name' => $name], '', true) . "' width=100 > ";
        echo 123;
    }

    /**
     * @throws Exception
     */
    public function showImg()
    {
        $name = input("name");
        $file = root_path("public") . "uploads/ico/20240321/" . $name;
        if (!file_exists($file)) {
            throw new Exception("查无文件");
        }
        $content = file_get_contents($file);
        header("Content-Type:image");
        echo $content;
        die();
    }
}