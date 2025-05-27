<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/28
 * Time: 10:35
 */

namespace app\index\controller;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Config\ConfigModel;
use app\AppFactory\Kernel\Model\Fluorite\FluoriteProjectTaskModel;
use app\AppFactory\Kernel\Model\OpenPlatform\OpenPlatformWxModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersModel;
use app\AppFactory\Kernel\Model\Store\StoreModel;
use app\AppFactory\Kernel\Model\Strategy\StrategyPayeeModel;
use app\AppFactory\Kernel\Support\FileDownload;
use app\AppFactory\Kernel\Support\TDESUtil;
use app\BaseController;
use EasyWeChat\Factory;
use GatewayWorker\Lib\Gateway;
use think\facade\Config;
use think\facade\Queue;
use think\facade\View;

class Test extends BaseController
{
    public function testUrl()
    {
        $url = url("http/pay.wx/refundOrderNotify", [], "", true);
        dump($url);
    }

    public function testNotice()
    {
        $terminal_no = "test0001";
        $store = StoreModel::getFind(['terminal_no' => $terminal_no,'status' => 1],'store_id,store_name,store_manager,pic,terminal_no,contacts,address,mobile,heart_time,store_type','');
        $store = $store->toArray();
        dump($store);
        $store['online'] = 2;
        $app = AppFactory::gatewayWorker();
        $result = $app->receiveTerminal->sendOnOfflineNotice($store);
        dump($result);

    }
    public function testCheckFluorite()
    {
        $json = '{"first":"您好！{{nickname}}，摄像头已上线","keyword1":"deviceSerial","keyword2":"now","keyword3":"address","remark":"如有疑问请联系客服：{{mobile}}"}';
        $app = AppFactory::timeTask();
        $result = $app->fluoriteDevice->checkCameraOnline();
        dump($result);
    }

    public function testSendTemplate()
    {
        $data = [
            "first",
            "keyword1",
            "keyword2",
            "keyword3",
        ];
        dump(json_encode($data));

        $sendData = [
            'store_id' => 14,
            'noticeType' => 4,
            'body' => [
                "first" => '您好，门店已离线',
                "keyword1" => date('Y-m-d H:i:s'),
                "keyword2" => "测试地址",
                "keyword3" => "上线",
            ],
        ];
        $app = AppFactory::notice();
        $result = $app->wxTemplate->send($sendData);
        dump($result);
    }

    public function testAli()
    {
        $code = input('code');
        $payee = StrategyPayeeModel::getFind(['sp_id' => 9]);
        $config = json2arr($payee['content']);
        dump($config);
        $oauthApp = \AliPay\Factory::system($config);
        $aliAccessToken = $oauthApp->oauth->Token($code);
        dump($aliAccessToken);
        $app = \AliPay\Factory::user($config);
        $result = $app->info->share($aliAccessToken['access_token']);

        dump($result);
    }

    public function testWxTemplate()
    {
        $data['store_id'] = 14;
        $data['noticeType'] = 1;
        $data['urlQuery'] = [];
        $data['miniQuery'] = [];
        $app = AppFactory::notice();
        $result = $app->wxTemplate->send($data);
        dump($result);
    }


    public function testSaveVideo()
    {
        $app = AppFactory::timeTask();
        $result = $app->fluoriteVideo->saveYesterday();
        dump($result);
    }


    protected $order;

    public function testSortArr()
    {
//        $data = [
//            [
//                "sm_id" => "",
//                "manager_id" => "",
//                "sort" => "",
//                "si_id" => "",
//                "income_value" => "",
//            ],
//        ];

        $data = [];
        $app = AppFactory::management();
        $si = $app->strategyIncome->getStrategyIncomeByStoreId(14);
        if ($si) {
            $smField = "sm_id,manager_id,sort";
            foreach ($si as $sik => $siv) {
                $getField = $smField . "," . $siv["si_id"] . " as si_id,(" . $siv['income_value'] . ") as income_value, " . $siv['transfer_method'] . " as transfer_method";
                $sm = $app->strategyManager->getStrategyManagerList(['s_id' => $siv['si_id'], 's_type' => 1], 0, $getField);
                if ($sm) $sm = $sm->toArray();
                $data = array_merge($data, $sm);
            }
        }
        echo "初始数据";
        dump($data);
        array_multisort(array_column($data, 'sort'), SORT_ASC, $data);
        echo "排序值排序";
        dump($data);
        $data = super_unique($data, "manager_id");
        echo "去重";
        dump($data);
//        array_multisort(array_column($data,'manager_id'),SORT_ASC, $data);
//        echo "账号ID排序";
//        dump($data);
        $this->order['total_price'] = 100;
        $this->order['payment_type'] = 4;
        $radio = 100;
        $this->handleRadio($radio);
        dump($radio);
//        die();
        foreach ($data as $key => $value) {
            $radio = bcsub($radio, $value['income_value']);
            if ($radio > 0) {
                $income_amount = bcmul($this->order['total_price'], bcmul($value['income_value'], 0.01, 3), 3);
                if ($this->order['total_price'] > 0) {
                    $value['income_amount'] = $income_amount;
                    $temp[] = $value;
                }
            }
        }
        dump($temp);
    }

    public function handleRadio(&$radio)
    {
        dump($radio);
        if ($this->order['payment_type'] == 4) {
            $radio = bcmul(bcdiv(bcsub($this->order['total_price'], 0.01, 3), $this->order['total_price'], 3), 100);
        }
        return $radio;
    }

    public function testTimeTask()
    {
        $app = AppFactory::timeTask();
//        $result = $app->terminal->countOnline();
//        dump($result);
        $result = $app->fluoriteFile->download();
        dump($result);
//        $result = $app->terminal->checkClose();
    }

    public function testEncrypt()
    {
        $data = ["a" => "1231241412", "time" => time()];
        $key = Config::get('app.salt');
        dump($key);
        $result = TDESUtil::encrypt(arr2json($data), $key);
        dump($result);
        $data_arr = TDESUtil::decrypt($result, $key);
        dump($data_arr);
    }

    public function index()
    {
        return View::fetch();
    }

    public function testCheckClose()
    {
        $app = AppFactory::timeTask();
        $app->terminal->countOnline();
    }

}