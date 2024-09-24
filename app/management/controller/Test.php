<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 15:00
 */

namespace app\management\controller;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Auth\AuthManagerLogModel;
use app\AppFactory\Kernel\Model\Auth\AuthManagerModel;
use app\AppFactory\Kernel\Model\Earth\EarthCitiesModel;
use app\AppFactory\Kernel\Model\Earth\EarthRegionsModel;
use app\AppFactory\Kernel\Model\Earth\EarthStatesModel;
use app\AppFactory\Kernel\Model\Machine\MachineCheckStockCountView;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersDailyCountView;
use app\AppFactory\Kernel\Support\TDESUtil;
use app\AppFactory\Management\Application;
use app\BaseController;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;

class Test extends BaseController
{
    public function dumpSession()
    {
        dump(session(""));
        $check = checkFrequency("dumpSession",10);
        dump($check);
    }

    public function testUpdateImgPrefix()
    {
        $filePath = public_path() . "updateImagePath.sql";
        // 打开文件，如果文件不存在则创建
        $file = fopen($filePath, 'a+');
        $sql = "show tables";
        $tables = Db::query($sql);
        foreach ($tables as $key => $value) {
            $table_name = $value['Tables_in_kiosk'];
            $sql = "SELECT COLUMN_NAME as 'Field'
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE table_schema = 'kiosk' AND 
        COLUMN_NAME <> 'create_time' AND COLUMN_NAME <> 'update_time'  AND COLUMN_NAME <> 'update_id' ";
            if ($table_name) $sql .= " AND table_name='$table_name'";
            $sql .= " group by COLUMN_NAME";
            $fieldList = Db::query($sql);
            $data = Db::query("SELECT * FROM " . $table_name);
            foreach ($data as $dk => $dv) {
                $update = [];
                foreach ($fieldList as $key => $value) {
                    if (strpos($dv[$value['Field']],"uploads") == 1) {
                        if (strpos($dv[$value['Field']],",") !== false) {
                            $fieldTemp = explode(",",$dv[$value['Field']]);
                            $tempArr = [];
                            foreach ($fieldTemp as $v) {
                                if ($v && strpos($v,env("APP.host")) === false ) {
                                    $v = env("APP.host") . $v;
                                }
                                $tempArr[] = $v;
                            }
                            $update[] = "`" . $value['Field'] . "` = '"  . implode(",",$tempArr) . "'";
                        } else {
                            if (strpos($dv[$value['Field']],env("APP.host")) === false && $dv[$value['Field']]) {
                                $update[] = "`" . $value['Field'] . "` = '" . env("APP.host") . $dv[$value['Field']] . "'";
                            }
                        }
                    }
                }

                if ($update) {
                    $updateSql = "UPDATE FROM " . $table_name . " SET " . implode(",", $update) . " WHERE " . $fieldList[0]['Field'] . "=" . $dv[$fieldList[0]['Field']] . ";";
                    // 移动到文件末尾
                    fseek($file, 0, SEEK_END);
                    // 写入数据
                    fwrite($file, $updateSql . "\r\n");
                }
            }
        }
        // 关闭文件
        fclose($file);
        echo "OK";
    }

    public function testFieldColumn()
    {
        $result = AuthManagerLogModel::getFieldComment();
        dump($result);
//        return returnState(200,'success',$result);
    }
    public function testMoney()
    {
        $money = "12345";
        dump($money);
        $wan = floor($money / 10000) . "万";
        $qian = floor(($money % 10000) / 1000) . "千";
        $bai = floor(($money % 1000) / 100) . "百";
        dump($money % 100);
        $ten = floor(($money % 100) / 10) . "十";
        $one = floor(($money % 10) / 1) . "个";
        dump($wan.$qian.$bai.$ten.$one);
    }

    public function testView()
    {
        $result =  SaleOrdersDailyCountView::getFind([],"*","countDate desc")->toArray();
//        $result = MachineCheckStockCountView::getList([]);
        dump($result);
    }
    public function testTemplateView()
    {
        $fields = '[{"key":"loop","options":"bars","type":"SELECT","display":{"en_US":"Image Loop Mode",
"ja_JP": "イメージループモード","zh_CN":"轮播方式","zh_TW":"輪播方式"}}]';
        $plugin = [
            "plugin_name" => "广告插件",
            "display_name" => '{"zh-cn":"广告插件","en":"advertise plugin"}',
            "type" => 1,
            "fields" => json2arr($fields)
        ];
        $plugin_data = [
            "layout_id" => 1,
            "plugin_id" => 1,
            "height" => 300,
            "width" => 500,
            "left" => 20,
            "top" => 30,
            "plugin" => $plugin,
        ];
        dump($plugin_data);
        $data = [
            "name" => "测试视图",
            "template_id" => "1",
            "height" => 1920,
            "width" => 1080,
            "plugin_data" => json_encode($plugin_data,256+64),
        ];
        dump($data);
        return arr2json($data);

    }
    public function makeStreet()
    {

        $province = EarthStatesModel::getList(['country_id' => 44])->toArray();
        $state_ids = array_column($province,'id');
//        $state_ids = array_search(,$province);
//        dump($state_ids);
//        dump($state_ids);
        $where[] = ['state_id' , 'in',$state_ids];
        dump($where);
        $city = EarthCitiesModel::getList($where)->toArray();
        dump($city);
//        $region = EarthRegionsModel::getList([''])
        die();


        foreach ($province as $pk => $pv) {
            dump($pv);
            $pro = Db::name("city")->where(['city_title' => $pv['cname']])->find();
            dump($pro);
            echo "<br>";
        }
    }
    public function testWeek()
    {
        $week = date("w");
        dump($week);

    }
    public function testDateTime()
    {
        $time = 1688109246;
        dump($time);
        dump(date("Y-m-d H:i:s",$time));
        dump(date("Y-m-d 23:59:59",$time));
        dump(strtotime(date("Y-m-d 23:59:59",$time)));
    }
    public function testArrValue()
    {
        $data = [
            "door" => 1,
            "lock" => 2,
            "button" => 3,
        ];
        dump(array_values($data));
        dump(implode("",array_values($data)));
    }
    /**
     * @var Application
     */
    protected $app;
    protected function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        $manager = AuthManagerModel::getFind(['manager_id' => 1]);
        $this->app = AppFactory::management($manager->getData());
    }

    public function getToken()
    {
        $manager = AuthManagerModel::getFind(['manager_id' => 1]);
        session("manager",$manager);
        $token_arr = [
            "session_id" => session_id(),
            "manager_id" => $manager['manager_id'],
            "timeout" => time(),
        ];
        $key = Config::get("app.salt");
        $token = TDESUtil::encrypt(json_encode($token_arr),$key);
        dump($token);
        actionLog($token,'token');
    }

    public function checkF()
    {
        $controller = request()->controller();
        $action = request()->controller();
    }

    public function qrCode()
    {
        $url = "http://www.baidu.com";
        $app = AppFactory::management();
        $config = [
            "folder" => "enQr",
            "name" => "en_2",
//            "text" => "",
            "logoPath" => true,
            "size" => "500",
            "margin" => 5,
            "resizeToWidth" => 80,
        ];
        $qr = $app->warehouseOrder->makeQr($url,$config);
        dump($qr);
    }

    public function getChildIds()
    {
        $manager = AuthManagerModel::getFind(['manager_id' => 1]);
        $app = AppFactory::management($manager->getData());
        echo "time1:",date("Y-m-d H:i:s"),"<br>";
        $ids = $app->authManager->getChildIdList($manager['manager_id'],[],$manager['level'],3);
        echo "time2:",date("Y-m-d H:i:s"),"<br>";
        $parent = $app->authManager->getParentIdList($manager['pid']);
        dump($parent);

    }

//    public function validateTime()
//    {
//         md5("dkm" . Config::get("app.salt"));
//        Url::bind("test/validateTime");
//        $time = "10:00";
//        dump(validateDate($time,"i:s"));
//        $int = "3665";
//        dump(Int2HourMinuteSec($int,3));
//    }
}