<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 15:00
 */

namespace app\management\controller;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Model\Activity\ActivityMachineModel;
use app\AppFactory\Kernel\Model\Activity\Coupon\ActivityCouponUsedModel;
use app\AppFactory\Kernel\Model\Activity\Fd\ActivityFdUsedModel;
use app\AppFactory\Kernel\Model\Activity\Lottery\ActivityLotteryUsedModel;
use app\AppFactory\Kernel\Model\Activity\Pick\ActivityPickCodeModel;
use app\AppFactory\Kernel\Model\Advertisement\AdvertisementPushModel;
use app\AppFactory\Kernel\Model\Advertisement\AdvertisementRecordModel;
use app\AppFactory\Kernel\Model\Api\ApiAdvanceModel;
use app\AppFactory\Kernel\Model\Auth\AuthManagerLogModel;
use app\AppFactory\Kernel\Model\Auth\AuthManagerMachineModel;
use app\AppFactory\Kernel\Model\Auth\AuthManagerModel;
use app\AppFactory\Kernel\Model\Earth\EarthCitiesModel;
use app\AppFactory\Kernel\Model\Earth\EarthRegionsModel;
use app\AppFactory\Kernel\Model\Earth\EarthStatesModel;
use app\AppFactory\Kernel\Model\Goods\GoodsChangeModel;
use app\AppFactory\Kernel\Model\Goods\GoodsHitModel;
use app\AppFactory\Kernel\Model\Goods\GoodsMultipleMachineModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelReplenishmentModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelStockModel;
use app\AppFactory\Kernel\Model\Machine\MachineCheckStockCountView;
use app\AppFactory\Kernel\Model\Machine\MachineCheckStockModel;
use app\AppFactory\Kernel\Model\Machine\MachineConfigModel;
use app\AppFactory\Kernel\Model\Machine\MachineErrorCodeModel;
use app\AppFactory\Kernel\Model\Machine\MachineGoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineGroupMgModel;
use app\AppFactory\Kernel\Model\Machine\MachineHelpModel;
use app\AppFactory\Kernel\Model\Machine\MachineInfoModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\Machine\MachineMqRecordModel;
use app\AppFactory\Kernel\Model\Machine\MachineOnlineDetailsModel;
use app\AppFactory\Kernel\Model\Machine\MachineOnlineModel;
use app\AppFactory\Kernel\Model\Machine\MachineOnOffModel;
use app\AppFactory\Kernel\Model\Machine\MachineVersionPlanModel;
use app\AppFactory\Kernel\Model\Machine\MachineViewModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleHotelModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersDailyCountView;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersRefundModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersRevenueModel;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersUnclaimedModel;
use app\AppFactory\Kernel\Model\Trip\TripCityModel;
use app\AppFactory\Kernel\Model\Trip\TripMultipleMachineModel;
use app\AppFactory\Kernel\Support\TDESUtil;
use app\AppFactory\Management\Application;
use app\BaseController;
use Overtrue\Pinyin\Pinyin;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;

class Test extends BaseController
{
    public function testPyTripCity()
    {
        $list = TripCityModel::getList([],0,'tc_id,cityName',"","","");
        $list = $list->toArray();
        $pinyin = new Pinyin();
        foreach ($list as $key => $value) {
            $py = $pinyin->abbr($value['cityName']);
            $flag[] = TripCityModel::update(['tc_id' => $value['tc_id'],'initial' => $py]);
        }
        $result = flag_check($flag);
        dump($result);

    }

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

    public function changeMachineId()
    {
        $machine_id = input("machine_id");
        echo "开始处理", "<br>", "<br>";
        $whereM = [];
        if ($machine_id) $whereM['machine_id'] = $machine_id;
        $machineList = MachineModel::getList($whereM,0,'m_id,machine_id,machine_name');
        $machineList = $machineList->toArray();
        $tables = Db::query("SHOW TABLES");
        $tableList = array_column($tables,"Tables_in_kiosk");
        $columns = [];
        $fields = ["m_id","machine_id","machine_name"];
        foreach ($tableList as $tableName) {
            if ($tableName == "machine") continue;
            $fieldsList = Db::query('SHOW FULL COLUMNS FROM `' . $tableName . '` ' );
            // 获取Field字段名反转字段名为Key值
            $tableField = array_flip(array_column($fieldsList, 'Field'));
            // tableField和反转Key值的fields参数比较数据，返回交集，获取交集的Key值为需要处理的字段参数名
            $handleFields = array_keys(array_intersect_key($tableField,array_flip($fields)));
            if ($handleFields && in_array("machine_id",$handleFields)){
                // 获取表备注信息，为VIEW视图的表则跳过
                $comment = Db::query("SHOW TABLE STATUS WHERE `Name` = '$tableName'");
                $tableComment = $comment[0]['Comment'];
                if ($tableComment == "VIEW") continue;
                // 以表名为Key值，整理Fields参数集和Comment表备注信息
                $columns[$tableName] = ['fields' => $handleFields,'comment' => $tableComment];
            }
        }
//        dump($columns);

        foreach ($machineList as $key => $value) {
            echo "【", $value['machine_id'],"】","ID：", $value['m_id'], "，设备名称：",$value['machine_name'],"<br>";
            $where = [];
            $where['m_id'] = $value['m_id'];
            $where[] = ["machine_id","<>",$value['machine_id']];
            foreach ($columns as $tableName => $cv) {
                // 查询是否有存在设备编号不相等数据
                $select = Db::name($tableName)->where($where)->field($cv["fields"][0])->select()->toArray();
                if ($select) {
                    $update = [];
                    foreach ($cv['fields'] as $cvk => $field) {
                        $update[$field] = $value[$field];
                    }
                    $result = Db::name($tableName)->where($where)->update($update);
                    echo "修改", $cv['comment'], "：", Db::name("")->getLastSql(), "，结果：$result 行";
                    if ($result) echo "，修改成功","<br>";
                    else echo "，修改失败" , "<br>";
                }
            }
            echo "【", $value['machine_id'],"】处理完成","<br>","<br>";
        }
        echo "全部处理完成";
    }
}