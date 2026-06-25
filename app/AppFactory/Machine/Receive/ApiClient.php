<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/23
 * Time: 9:43
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Support\Trip\Trip;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityMachineTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickCodeTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickTrait;
use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementPushTrait;
use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementRecordTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerMachineTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerRoleTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthNodeTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrganizationTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthRoleNodeTrait;
use app\AppFactory\Kernel\Traits\Config\ConfigTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCitiesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCountriesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthRegionsTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthStatesTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryLangTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsCornerTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsLangTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsMultipleGoodsTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsMultipleMachineTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsMultipleTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsChangeTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelReplenishmentTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineCalibrationConfigTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineConfigLangTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineConfigTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineHelpTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineInfoTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineLangTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnOffTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineRefundGoodsLogTrait;
use app\AppFactory\Kernel\Traits\Machine\SimCardInfoTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineVersionPlanTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineViewTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelNightlyTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyIncomeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyManagerTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Kernel\Traits\Template\TopicPageTrait;
use app\AppFactory\Kernel\Traits\Template\TemplateViewTrait;
use app\AppFactory\Kernel\Traits\Wx\WxOfficialLoginTrait;
use app\AppFactory\Kernel\Traits\Wx\WxOfficialTrait;
use app\AppFactory\RabbitMq\MqProducer;
use app\machine\validate\VReceive;
use think\facade\Db;
use think\facade\View;
use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderRefundTrait;
use app\AppFactory\Kernel\Traits\Card\CardTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcBaseTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcGoodsTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcUserLoginInfoTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrgMachineChannelTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRefundTrait;
use app\AppFactory\Kernel\Model\Machine\PreReplenishmentDetailModel;
use app\AppFactory\Kernel\Model\Machine\PreReplenishmentLogModel;
use app\AppFactory\Kernel\Model\Machine\PreReplenishmentOrderModel;

class ApiClient extends ReceiveBaseClient
{
    use
        ActivityCouponTrait,
        ActivityCouponUsedTrait,
        ActivityGoodsTrait,
        ActivityMachineTrait,
        ActivityPickTrait,
        ActivityPickCodeTrait,
        AdvertisementPushTrait,
        AdvertisementRecordTrait,
        AuthOrganizationTrait,
        AuthManagerMachineTrait,
        AuthManagerRoleTrait,
        AuthRoleNodeTrait,
        AuthNodeTrait,
        ConfigTrait,
        GoodsTrait,
        GoodsLangTrait,
        GoodsCategoryLangTrait,
        GoodsCategoryTrait,
        GoodsChangeTrait,
        GoodsCornerTrait,
        GoodsMultipleTrait,
        GoodsMultipleGoodsTrait,
        GoodsMultipleMachineTrait,
        MachineViewTrait,
        MachineCalibrationConfigTrait,
        MachineConfigTrait,
        MachineConfigLangTrait,
        MachineInfoTrait,
        MachineLangTrait,
        MachineChannelTrait,
        MachineChannelReplenishmentTrait,
        MachineVersionPlanTrait,
        MachineGoodsTrait,
        MachineHelpTrait,
        MachineOnOffTrait,
        MachineRefundGoodsLogTrait,
        SimCardInfoTrait,
        MachineTrait,
        TopicPageTrait,
        TemplateViewTrait,

        EarthCountriesTrait,
        EarthStatesTrait,
        EarthCitiesTrait,
        EarthRegionsTrait,

        BeforeOrderPaymentTrait,
        AfterOrderPaymentTrait,
        SaleOrdersTrait,
        SaleOrdersRevenueTrait,
        SaleHotelTrait,
        SaleHotelNightlyTrait,
        StrategyIncomeTrait,
        StrategyManagerTrait,
        StrategyPayeeTrait,
        StrategyMachineTrait,
        WxOfficialTrait,
        WxOfficialLoginTrait,
        afterOrderRefundTrait,
        CardTrait,
        WcBaseTrait,
        WcGoodsTrait,
        WcUserLoginInfoTrait,
        AuthOrgMachineChannelTrait,
        SaleOrdersRefundTrait;


    public $card_retail_price = 0.01;
    public $card_default_pwd = '123456';
    public $receipt_code1 = "/uploads/adv/20250618/41da4aa9c2e34fb84b123c8d39eba214.png";
    public $receipt_code2 = "/uploads/adv/20251022/0e543e3d6e1861bd59fed97194fd3f3f.jpg";
    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->dataRecord();
    }


    /**
     * 销毁实例时触发
     */
    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $result = $this->updateMachineMqRecord(['status' => 2, 'msg_id' => $this->data['msg_id']], ['msg_id' => $this->data['msg_id']]);
        actionLog($result, '处理完成时修改状态为已处理');
    }


    protected $order;
    protected $refundTradeNo;

    /**
     * 登录验证
     * @return array|string
     */
    public function login()
    {
        $where['m_id'] = $this->machine['m_id'];
        $manager_id = $this->getAuthManagerMachineValue(['account' => $this->data['account'], 'm_id' => $this->machine['m_id']], 'manager_id');
        if (!$manager_id) {
            return $this->rFail($this->lang("VLogin.not_manager"));
        }
        $manager = $this->getAuthManagerFind(['manager_id' => $manager_id], 'manager_id,pid,nickname,account,pic,password,status,ao_id,use_role_template');
        if (!$manager) return $this->rFail($this->lang("VLogin.account_pwd_error"));
        $manager = $manager->toArray();
        if ($manager['password'] != md5($this->data['password'] . config("app.salt")))
            return $this->rFail($this->lang("VLogin.account_pwd_error"));
        $nodeList = $this->getManagerNodeList($manager);
        if (!$nodeList)
            return $this->rFail($this->lang("VLogin.permission_denied"));
        $loginNode = array_column($nodeList->toArray(), 'url');
        if (!in_array("/machine/receive/login", $loginNode)) {
            return $this->rFail($this->lang("VLogin.permission_denied"));
        }
        if ($manager['status'] == 2) return $this->rFail($this->lang("VLogin.account_disabled"));
        unset($manager['password'], $manager['status']);
        $manager['nodeList'] = $nodeList;
        actionLog($manager, '返回的账号数据');
        return $this->r(200, $this->lang("VLogin.login_success"), $manager);
    }

    /**
     * 获取微信登录二维码
     * @return array|\think\response\Json
     */
    public function wxLoginQrCode()
    {
        $where['ao_id'] = $this->machine['ao_id'];
        $where['status'] = 1;
        $config = $this->getWxOfficialFind($where, '*', "id desc");
        if (!$config) return $this->r(300, $this->lang("VWxLogin.wx_no_data"));
        $config = $config->toArray();
        $insert = [
            "wx_id" => $config['id'],
            "app_id" => $config['app_id'],
            "m_id" => $this->machine['m_id'],
            "machine_id" => $this->machine['machine_id'],
            "ip" => request()->ip(),
            "login_type" => 2,
            "ao_id" => $config['ao_id'],
        ];
        $id = $this->addWxOfficialLogin($insert);
        if (!$id) return $this->r(300, $this->lang("action_fail"));
        $loginUrl = $this->getUrl("/wx/login/scanLogin/login_id/$id/time/" . time());
        $this->updateWxOfficialLogin(['id' => $id, "login_url" => $loginUrl]);
        return $this->r(200, $this->lang("action_success"), ["id" => $id, "login_url" => $loginUrl]);
    }

    /**
     * 退出登录
     * @return array|string
     */
    public function logout()
    {
        return $this->r(200, $this->lang("VLogin.logout_success"));
    }

    /**
     * 获取系统配置信息
     * @return array|string
     */
    public function systemInfo()
    {
        $pIds = $this->getAuthManagerMachineColumn(['m_id' => $this->machine['m_id']], 'manager_id');
        $pIds = array_merge($pIds, $this->getParentIdList($this->machine['creator']));
        $pIds[] = $this->machine['creator'];
        $pIds[] = 1;
        $systemInfo = $this->getConfigContent([['creator', 'in', $pIds], "config_switch" => 1, 'config_name' => "systemInfo"]);
        return $this->r(200, $this->lang("query_success"), $systemInfo);
    }

    public function ip()
    {
        $ip = request()->ip();
        return $this->rQ($ip);
    }

    /**
     * 查询设备信息
     * @return array|string
     */
    public function machine()
    {
        if (isset($this->machine['country_id']) && $this->machine['country_id']) $this->machine['country'] = $this->getEarthCountriesFind(['id' => $this->machine['country_id']], 'code,name,cname');
        if (isset($this->machine['state_id']) && $this->machine['state_id']) $this->machine['state'] = $this->getEarthStatesFind(['id' => $this->machine['state_id']], 'code,name,cname');
        if (isset($this->machine['city_id']) && $this->machine['city_id']) $this->machine['city'] = $this->getEarthCitiesFind(['id' => $this->machine['city_id']], 'code,name,cname');
        if (isset($this->machine['regions_id']) && $this->machine['regions_id']) $this->machine['regions'] = $this->getEarthRegionsFind(['id' => $this->machine['regions_id']], 'code,name,cname');
        return $this->r(200, 'SUCCESS', $this->machine);
    }

    /**
     * 设备主体多语言
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function machineLang()
    {
        $result = $this->getMachineLangList(['m_id' => $this->machine['m_id']]);
        return $this->rQ($result);
    }

    /**
     * 获取并同步设备校准页配置
     *
     * 规则：
     * 1. 数据库为空时，以设备上报配置初始化；
     * 2. 设备版本大于数据库版本时，写入新版本全量配置；
     * 3. 版本相同或更小，不改数据库，仅返回当前最新版本。
     *
     * @return array|\think\response\Json
     */
    public function machineCalibrationConfig()
    {
        $mId = $this->machine['m_id'];
        //获取machine信息
        $machineData = $this->getMachineFind(['m_id' => $mId], "machine_id");
        $machineConfig = $this->getMachineConfigFind(['m_id' => $mId], 'remote_calibration');
        $remoteCalibration = $machineConfig['remote_calibration'] ?? 0;
        if ($remoteCalibration != 1) {
            return $this->r(300, '先开启设备远程校准');
        }
        $incomingList = $this->getIncomingCalibrationList();
        $incomingVersion = $this->getIncomingCalibrationVersion($incomingList);

        $latestRow = $this->getMachineCalibrationConfigFind(['m_id' => $mId], 'version,id', 'id desc');
        $latestVersion = $latestRow ? intval($latestRow['version']) : 0;

        if ($latestVersion <= 0) {
            if (!empty($incomingList)) {
                $initVersion = $incomingVersion > 0 ? $incomingVersion : 1;
                $this->insertCalibrationRows($incomingList, $mId, $machineData['machine_id'] ?? '', $initVersion);
                $latestVersion = $initVersion;
            }
        } elseif ($incomingVersion > $latestVersion && !empty($incomingList)) {
            $this->insertCalibrationRows($incomingList, $mId, $machineData['machine_id'] ?? '', $incomingVersion);
            $latestVersion = $incomingVersion;
        } else {
            $latestVersion = $incomingVersion > $latestVersion ? $incomingVersion : $latestVersion;
        }


        $list = $this->getMachineCalibrationConfigList(
            ['m_id' => $mId, 'version' => $latestVersion],
            0,
            'key,value,value_type,version',
            'id asc'
        );
        $list = $list ? $list->toArray() : [];
        $res = [];
        if ($list) {
            foreach ($list as $k => $item) {
                $res['version'] = intval($item['version']);
                $res[$item['key']] = $this->castCalibrationValueByType($item['value'], $item['value_type'] ?? 'string');
            }
        }
        return $this->rQ($res);
    }

    /**
     * 兼容不同字段名，获取设备上传的校准配置数组
     * @return array
     */
    protected function getIncomingCalibrationList()
    {
        $list = [];
        if (isset($this->data['data']) && is_array($this->data['data'])) {
            $list = $this->data['data'];
        } elseif (isset($this->data['data']) && is_string($this->data['data']) && $this->data['data'] !== '') {
            $decodeData = json2arr($this->data['data']);
            if (is_array($decodeData)) {
                $list = $decodeData;
            }
        } 
        return $list;
    }

    /**
     * 获取设备上传的版本号，优先读取 data 内 key=version 的值
     * @param array $list
     * @return int
     */
    protected function getIncomingCalibrationVersion($list = [])
    {
        if (!empty($list) && is_array($list)) {
            foreach ($list as $row) {
                if (!is_array($row)) {
                    continue;
                }
                if (isset($row['key']) && strtolower((string)$row['key']) === 'version') {
                    if (isset($row['value']) && $row['value'] !== '') {
                        return intval($row['value']);
                    }
                    return 0;
                }
            }
        }

        return isset($this->data['version']) && $this->data['version'] !== '' ? intval($this->data['version']) : 0;
    }

    /**
     * 写入某一版本的全量校准配置
     * @param array $rows
     * @param int $mId
     * @param string $machineId
     * @param string $version
     */
    protected function insertCalibrationRows($rows, $mId, $machine_id, $version)
    {
        $seenKey = [];
        foreach ($rows as $row) {
            if (!isset($row['key']) || $row['key'] === '') {
                continue;
            }
            $key = $row['key'];
            if (isset($seenKey[$key])) {
                continue;
            }
            $seenKey[$key] = 1;

            // 幂等保护：同设备同版本同key存在则不重复写入。
            $exists = $this->getMachineCalibrationConfigValue(['m_id' => $mId, 'version' => $version, 'key' => $key], 'id', 'version desc');
            if ($exists) {
                continue;
            }

            $title = isset($row['title']) && $row['title'] !== '' ? $row['title'] : $key;
            $valueType = $this->detectCalibrationValueType($row['value'] ?? null, $row['value_type'] ?? '');
            $value = $this->normalizeCalibrationValueForStorage($row['value'] ?? null, $valueType);
            $this->addMachineCalibrationConfig([
                'm_id' => $mId,
                'machine_id' => $machine_id,
                'name' => $title,
                'version' => $version,
                'key' => $key,
                'value' => $value,
                'value_type' => $valueType,
                'desc' => isset($row['desc']) ? $row['desc'] : '',
            ]);
        }
    }

    /**
     * 根据前端传入值判断配置值类型
     * @param mixed $value
     * @param string $inputType
     * @return string
     */
    protected function detectCalibrationValueType($value, $inputType = '')
    {
        $inputType = strtolower((string)$inputType);
        if (in_array($inputType, ['string', 'int', 'float', 'bool'], true)) {
            return $inputType;
        }
        if (is_bool($value)) {
            return 'bool';
        }
        if (is_int($value)) {
            return 'int';
        }
        if (is_float($value)) {
            return 'float';
        }
        return 'string';
    }

    /**
     * 入库前统一转为字符串存储
     * @param mixed $value
     * @param string $valueType
     * @return string
     */
    protected function normalizeCalibrationValueForStorage($value, $valueType)
    {
        if ($value === null) {
            return '';
        }
        if ($valueType === 'bool') {
            return $value ? '1' : '0';
        }
        return (string)$value;
    }

    /**
     * 读取一条设备校准配置
     * @param array $where
     * @param string $field
     * @param string $order
     * @return array|null
     */
    protected function getMachineCalibrationConfigFind($where, $field = '*', $order = '')
    {
        $query = Db::name('machine_calibration_config')->where($where)->field($field);
        if ($order) {
            $query->order($order);
        }
        return $query->find();
    }

    /**
     * 读取设备校准配置列表
     * @param array $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \think\Collection|\think\Paginator
     */
    protected function getMachineCalibrationConfigList($where, $pageNum = 0, $field = '*', $order = '')
    {
        $query = Db::name('machine_calibration_config')->where($where)->field($field);
        if ($order) {
            $query->order($order);
        }
        if ($pageNum) {
            return $query->paginate($pageNum);
        }
        return $query->select();
    }

    /**
     * 获取设备校准配置字段值
     * @param array $where
     * @param string $value
     * @param string $order
     * @return mixed
     */
    protected function getMachineCalibrationConfigValue($where, $value, $order = '')
    {
        $query = Db::name('machine_calibration_config')->where($where);
        if ($order) {
            $query->order($order);
        }
        return $query->value($value);
    }

    /**
     * 新增设备校准配置
     * @param array $insert
     * @return int|string
     */
    protected function addMachineCalibrationConfig($insert)
    {
        return Db::name('machine_calibration_config')->insertGetId($insert);
    }

    /**
     * 按 value_type 将字符串值转换回对应类型
     * @param mixed $value
     * @param string $valueType
     * @return mixed
     */
    protected function castCalibrationValueByType($value, $valueType)
    {
        switch ((string)$valueType) {
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'bool':
                if (is_bool($value)) {
                    return $value;
                }
                $v = strtolower(trim((string)$value));
                return in_array($v, ['1', 'true', 'yes', 'on'], true);
            default:
                return (string)$value;
        }
    }

    /**
     * 设备商品信息
     * @return array|string
     */
    public function machineGoods()
    {
        $where['mg.m_id'] = $this->machine['m_id'];
        $goodsField = "mg.mg_id,mg.m_id,mg.machine_id,mg.g_id,mg.g_name,mg.gc_id,mg.gc_name,mg.pic,mg.sku,mg.bar_code,mg.cost_price,mg.market_price,mg.retail_price,mg.gift_points,mg.available_stock,
        mg.disabled_stock,mg.reserve_stock,mg.standby_stock,mg.pre_loading_stock,mg.is_shelf,g.sell_channel,g.exter_url";
        return $this->r(200, "SUCCESS", $this->getMachineGoodsListJoinGoods($where, $this->data['pageNum'] ?? 0, $goodsField));
    }

    /**
     * 终端提交设备商品库数据，新增或修改
     * @return mixed
     */
    public function subMachineGoods()
    {
        return $this->terminalSubMachineGoods();
    }

    /**
     * 设备货道信息
     * @return array|string
     */
    public function machineChannel()
    {
        $where['m_id'] = $this->machine['m_id'];
        $where['is_hidden'] = 2;
        if (isset($this->data['mc_id']) && $this->data['mc_id']) $where['mc_id'] = $this->data['mc_id'];
        $channelField = "mc_id,m_id,machine_id,channel_code,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,length,width,width2,height,height2,
        cost_price,market_price,retail_price,gift_points,x_axis,y_axis,shelf_way,cost_points,
        slot_hole,capacity,frozen_stock,stock,is_gift,is_recommend,stock_warning,recoverable,heat,channel_position,fetch_mode,status";
        $mcList = $this->getMachineChannelList($where, 0, $channelField, 'channel_code asc');
        if ($mcList) {
            $mcList = $mcList->toArray();
            foreach ($mcList as $key => $mc) {
                $where = [];
                $where[] = ['gc.start_time', "<=", time()];
                $where['ag.g_id'] = $mc['g_id'];
                $where['am.m_id'] = $mc['m_id'];
                $where[] = ['status', "<", 3];
                $corner = $this->getGoodsCornerFindByAmAg($where, 'gc.id,gc.corner_name,gc.corner_type,gc.pic,gc.style,gc.position,gc.start_time,gc.end_time,gc.status');
                if ($corner) {
                    actionLog($corner, '角标数据');
                    $updateCorner['id'] = $corner['id'];
                    if ($corner['status'] == 1) {
                        $updateCorner['status'] = 2;
                        $corner['status'] = 2;
                    }
                    if ($corner['end_time'] > 0 && $corner['end_time'] < time()) {
                        $updateCorner['status'] = 3;
                        $corner = null;
                    }
                    if ($updateCorner) {
                        $this->updateGoodsCorner($updateCorner);
                    }
                }
                $mc['gc_sort'] = $this->getMachineGoodsValue(['mg_id' => $mc['mg_id']], 'gc_sort');
                $mc['corner'] = $corner;
                $mcList[$key] = $mc;
            }
        }
        actionLog($mcList, '返回的货道数据');
        return $this->r(200, "SUCCESS", $mcList);
    }

    /**
     * http请求设备故障码上报接口
     * @return array|\think\response\Json
     */
    public function sendErro()
    {
        try {
            $this->message = [
                "errorCode" => $this->data['errorCode'] ?? "",
                "msg" => $this->data['msg'] ?? "",
                "error_position" => $this->data['error_position'] ?? "",
                "creator_id" => $this->data['creator_id'] ?? 0,
            ];
            $this->errorCode();
            return $this->r(200, $this->lang("action_success"));
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 终端提交货道数据，新增或修改
     * @return array|string
     */
    public function subChannel()
    {
        return $this->terminalSubChannel();
    }

    /**
     * 货道补货
     * @return mixed
     */
    public function channelReplenishment()
    {
        return $this->terminalReplenishment();
    }

    /**
     * 更换货道商品
     * @return array|string
     */
    public function changeChannelGoods()
    {
        actionLog($this->data, '更换货架商品数据');
        $this->startTrans();

        try { // 清空旧商品库存，生成退货记录
            $mc = $this->getMachineChannelFind(['mc_id' => $this->data['mc_id']]);
            $mc = obj2arr($mc);
            if ($mc['frozen_stock'] > 0) {
                return $this->rFail($this->lang("VChangeChannelGoods.mg_no_data"));
            }
            $insertGChange = [
                "m_id" => $this->machine['m_id'],
                "machine_id" => $this->machine['machine_id'],
                "machine_name" => $this->machine['machine_name'],
                "mc_id" => $mc['mc_id'],
                "channel_code" => $mc['channel_code'],
                "mg_id" => $mc['mg_id'],
                "g_id" => $mc['g_id'],
                "g_name" => $mc['g_name'],
                "gc_id" => $mc['gc_id'],
                "gc_name" => $mc['gc_name'],
                "pic" => $mc['pic'],
                "sku" => $mc['sku'],
                "bar_code" => $mc['bar_code'],
                "change_value" => $mc['stock'],
                "ao_id" => $this->machine['ao_id'],
                "creator" => $this->data['operator'],
            ];
            // 生成原商品退货记录
            if ($mc['stock'] > 0) {

                // 记录商品变化事件（换货-货架下货旧商品）
                $insertGChange["desc"] = $this->lang("goodsChange.terminal_exchange_mc_under_old");
                $insertGChange['position'] = 1;
                $insertGChange['type'] = 3;
                $this->addGoodsChange($insertGChange);

                // 原货架商品是设备商品库的，退回设备商品库备用库存
                if ($mc['mg_id'] > 0) {

                    // 记录商品变化事件（换货-设备商品库上货备用库存）
                    $insertGChange['desc'] = $this->lang("goodsChange.terminal_exchange_mg_inc_reserve_stock");
                    $insertGChange['position'] = 2;
                    $insertGChange['type'] = 2;
                    $this->addGoodsChange($insertGChange);

                    $flag[] = $this->setMachineGoodsInc(['mg_id' => $mc['mg_id']], 'standby_stock', $mc['stock']);
                }
                $repData = $this->handleRepData($mc, bcsub(0, $mc['stock']));
                $flag[] = $this->addMachineChannelReplenishment($repData);
                $mc['stock'] = 0;
            } // 有设置库存容量时重置库存容量
            if (isset($this->data['capacity']) && $this->data['capacity']) {
                $mc['capacity'] = $this->data['capacity'];
            }
            $quantity = $this->data['quantity'];
            if (isset($this->data['standby_quantity'])) $quantity += $this->data['standby_quantity'];
            if ($quantity > $mc['capacity']) {
                $this->rollbackTrans();
                return $this->rFail($this->lang("VChannelReplenishment.exceed_capacity_limit"));
            }
            // 初始化商品信息，默认重置为空商品
            $g = [
                'g_id' => 0,
                'g_name' => "",
                'gc_id' => 0,
                'gc_name' => "",
                'pic' => "",
                'sku' => "",
                'bar_code' => "",
                'cost_price' => 0,
                'market_price' => 0,
                'retail_price' => 0,
                'is_gift' => 2,
                'is_recommend' => 2,
                'recoverable' => 2,
                'heat' => 0,
                'release_time' => 0
            ];
            if ($this->data['g_id']) {
                // 查询商品库
                $g = $this->getGoodsFind(
                    ['g_id' => $this->data['g_id']],
                    'g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price,is_gift,is_recommend,recoverable,heat,release_time'
                );
                if (!$g) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VChangeChannelGoods.goods_no_data"));
                }
                $g = $g->toArray();
                actionLog($g, '商品库信息');
                // 设备商品库有相同商品，并且是未上架状态的，修改为已上架
                $mgShelf = $this->getMachineGoodsFind(['m_id' => $this->machine['m_id'], 'g_id' => $this->data['g_id']], 'mg_id,is_shelf');
                if ($mgShelf && $mgShelf['is_shelf'] == 2) {
                    $this->updateMachineGoods(['mg_id' => $mgShelf['mg_id'], 'is_shelf' => 1]);
                    actionLog($this->getLS(), '修改设备商品库为已上架状态');
                }
            }
            $mg = [];
            $mc['mg_id'] = 0;
            $insertGChange['mg_id'] = 0; // 有设备商品库ID时
            if ($this->data['mg_id']) {
                // 查询新商品，修改货道商品信息，重置库存为新数量，生成新的补货记录
                $mg = $this->getMachineGoodsFind(
                    ['mg_id' => $this->data['mg_id']],
                    'mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price,standby_stock,is_shelf'
                );
                if (!$mg) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VChangeChannelGoods.mg_no_data"));
                }
                $mg = $mg->toArray();
                // 换货提交的备用库存大于0
                if (isset($this->data['standby_quantity']) && $mg['standby_stock'] > 0 && $this->data['standby_quantity'] > 0) {

                    // 记录商品变化事件（换货-设备商品库下货备用库存）
                    $insertGChange["mg_id"] = $mg['mg_id'];
                    $insertGChange["g_id"] = $mg['g_id'];
                    $insertGChange["g_name"] = $mg['g_name'];
                    $insertGChange["gc_id"] = $mg['gc_id'];
                    $insertGChange["gc_name"] = $mg['gc_name'];
                    $insertGChange["pic"] = $mg['pic'];
                    $insertGChange["sku"] = $mg['sku'];
                    $insertGChange["bar_code"] = $mg['bar_code'];
                    $insertGChange["desc"] = $this->lang("goodsChange.terminal_exchange_mg_dec_reserve_stock");
                    $insertGChange["change_value"] = $this->data['standby_quantity'];
                    $insertGChange["position"] = 2;
                    $insertGChange["type"] = 3;
                    $this->addGoodsChange($insertGChange);

                    $flag[] = $this->setMachineGoodsDec(['mg_id' => $mg['mg_id']], 'standby_stock', $this->data['standby_quantity']);

                    // 生成备用库存补货记录
                    $repNewData = $this->handleRepData($mc, $this->data['standby_quantity']);
                    $flag[] = $this->addMachineChannelReplenishment($repNewData);
                    $mc['stock'] += $this->data['standby_quantity'];
                }

                actionLog($mg, '设备商品库信息');
                unset($mg['standby_stock']);
            }
            if (!$this->data['g_id']) {
                $mc['batch_number'] = "";
                $mc['manufacture_time'] = 0;
                $mc['sell_by_date'] = 0;
                $mc['frozen_stock'] = 0;
                $mc['update_price'] = 2;
            }
            $mc = array_merge($mc, $mg, $g);
            actionLog($mc, '要修改的货架数据');
            if (isset($this->data['quantity']) && $this->data['quantity'] > 0) {

                // 记录商品变化事件（换货-货架上货新商品）
                $insertGChange["mg_id"] = $mc['mg_id'];
                $insertGChange["desc"] = $this->lang("goodsChange.terminal_exchange_mc_display_new");
                $insertGChange["change_value"] = $mc['stock'];
                $insertGChange["position"] = 1;
                $insertGChange["type"] = 2;
                $this->addGoodsChange($insertGChange);

                // 生成上架补货记录
                $repNewData = $this->handleRepData($mc, $this->data['quantity']);
                $flag[] = $this->addMachineChannelReplenishment($repNewData);
                $mc['stock'] += $this->data['quantity'];
            }
            $flag[] = $this->updateMachineChannel($mc);
            actionLog($this->getLS(), '【SQL】修改货道信息');
            $result = $this->checkFlag($flag);
            $result ? $this->commitTrans() : $this->rollbackTrans();
            return $this->rAction($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 设备配置信息
     * @return array|string
     */
    public function machineConfig()
    {
        $where["m_id"] = $this->machine['m_id'];
        $configField = "*";
        $data = $this->getMachineConfigFind($where, $configField);
        if (isset($data['pay_type']) && $data['pay_type']) {
            $pay_type = explode(",", $data['pay_type']);
            if ($pay_type) {
                $sIds = $this->getStrategyMachineColumn(['m_id' => $this->machine['m_id'], 's_type' => 1], 's_id');
                if ($sIds) {
                    $payTypeList = $this->getStrategyPayeeList([['sp_id', 'in', $sIds], 'status' => 1], 0, 'sp_name,title,payee_type,ico');
                    $data['payTypeList'] = $payTypeList;
                }
            }
        }
        if(!isset($data['is_operating'])){
            $data['is_operating'] = $this->getMachineValue(['m_id' => $this->machine['m_id']], 'is_operating');
        }
        if(isset($data['limit_quantity'])){
            $data['cart_num_limit'] = $data['limit_quantity'];
        }
        return $this->rQ($data);
    }

    /**
     * 获取设备当前主题配置
     * @return array|\think\response\Json
     */
    public function topicPage()
    {
        $data = [
            'top_logo' => '',
            'bg_url' => '',
            'maintenance_bg' => '',
            'error_url' => '',
            'closed_url' => '',
            'verification_url' => '',
            'pickup_url' => '',
            'shipping_url' => '',
            'pickup_qrcode_1' => '',
            'pickup_qrcode_2' => '',
            'qr_code_url' => '',
            'scan_url' => '',
            'balance_url' => '',
            'card_url' => '',
            'out_goods_title' => '',
            'claim_goods_title' => '',
            'is_service_phone' => 2,
            'deal_fail_sub_title' => '',
            'deal_fail_title' => '',
            'deal_abnormal_pic' => '',
            'deal_success_sub_title'  => '',
            'deal_success_title' => '',
            'pickup_qrcode_text1' => '',
            'pickup_qrcode_text2' => ''
        ];
        
        $topicIds = $this->getTopicPageMachineColumn(['machine_id' => $this->machine['machine_id']], 'topic_id');
        if (!$topicIds) {
            return $this->rQ($data);
        }

        $topicIds = array_values(array_unique(array_filter($topicIds)));
        if (!$topicIds) {
            return $this->rQ($data);
        }
        $field = implode(',', array_keys($data));
        $topic = $this->getTopicPageFind([
            ['id', 'in', $topicIds],
            ['status', '=', 1],
        ], $field, 'id desc');

        if ($topic) {
            $topic = $topic->toArray();
            foreach ($data as $key => $value) {
                if (isset($topic[$key]) && $topic[$key] !== '' && $topic[$key] !== null) {
                    $data[$key] = $topic[$key];
                }
            }
        }

        return $this->rQ($data);
    }

    /**
     * 设备配置多语言数据列表
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function machineConfigLang()
    {
        $result = $this->getMachineConfigLangList(['m_id' => $this->machine['m_id']]);
        return $this->rQ($result);
    }

    /**
     * 设备营业配置
     * @return array|\think\response\Json
     */
    public function machineOnOff()
    {
        $where['m_id'] = $this->machine['m_id'];
        $onOffField = "moo_id,on_off_ckc,on_off_machine";
        $data = $this->getMachineOnOffFind($where, $onOffField, 'update_time desc');
        return $this->rQ($data);
    }

    /**
     * 设备其他信息
     * @return array|string
     */
    public function machineInfo()
    {
        $where['m_id'] = $this->machine['m_id'];
        $infoField = "mi_id,m_id,machine_id,pos,printer,scanner,cash_register,last_restart_time,operator,iccid,total_flow,remain_flow,
        valid_time,production_date,expiration_date";
        return $this->r(200, "SUCCESS", $this->getMachineInfoFind($where, $infoField));
    }

    /**
     * 设备帮助信息
     * @return array|string
     */
    public function machineHelp()
    {
        $where["machine_id"] = $this->machine['machine_id'];
        $helpField = "mh_id,pid,show,title,content,lang";
        $data = $this->getMachineHelpList($where, 0, $helpField);
        actionLog($this->getLS());
        return $this->rQ($data);
    }

    /**
     * 设备首页模板视图
     * @return array|string
     */
    public function machineView()
    {
        $where['m_id'] = $this->machine['m_id'];
        $where['status'] = 1;
        $mv = $this->getMachineViewFind($where, 'mv_id,view_id,m_id,machine_id,name,position,notes,publish_time,expire_time', 'mv_id desc');
        if ($mv) {
            $mv['details'] = $this->getTemplateViewFind(['id' => $mv['view_id']], '
                name,height,width,plugin_data
            ');
            return $this->r(200, 'SUCCESS', $mv);
        }
        return $this->r(100, $this->lang("query_mv_no_data"));
    }

    /**
     * 设备模板视图列表
     * @return array|string
     */
    public function machineViewList()
    {
        $where['m_id'] = $this->machine['m_id'];
        $where['status'] = 1;
        $where[] = function ($query) {
            $query->where("expire_time is null or expire_time > '" . time() . "'");
        };
        $mvList = $this->getMachineViewList($where, 0, 'mv_id,view_id,m_id,machine_id,name,position,notes,publish_time,expire_time', 'mv_id desc', '', 'position');
        if ($mvList) {
            $mvList = $mvList->toArray();
            foreach ($mvList as $key => $value) {
                $mvList[$key]['details'] = $this->getTemplateViewFind(['id' => $value['view_id']], 'name,height,width,plugin_data');
            }
            return $this->r(200, 'SUCCESS', $mvList);
        }
        return $this->r(100, $this->lang("query_mv_no_data"));
    }

    protected $goodsField = "
            g.g_id,g.g_name,g.gc_id,g.gc_name,g.model,g.pic,g.sku,g.bar_code,g.sku2,g.manufacturer,g.service_phone,g.performance,g.sell_channel,g.exter_url,g.is_gift,g.is_recommend,g.recoverable,g.heat,g.release_time,
            g.length,g.width,g.height,g.group_quantity,g.status,g.ao_id,g.update_time,g.desc,g.cost_price,g.market_price,g.retail_price,g.g_type,
            mg.mg_id,mg.available_stock,mg.disabled_stock,mg.reserve_stock,mg.standby_stock,mg.pre_loading_stock,mg.is_shelf";

    /**
     * 获取设备归属组织所有上级商品
     * @return array|string
     */
    public function goods()
    {
        $goodsList = [];
        $aoIds = $this->getPathIds($this->machine["ao_id"], 1);
        if ($aoIds) {
            $goodsList = $this->getGoodsJoinMachineGoodsList(
                [['g.ao_id', 'in', $aoIds]],
                $this->data['pageNum'] ?? 0,
                $this->goodsField,
                'g.g_id desc',
                $this->machine['m_id']
            );
            if (is_string($goodsList)) return $this->rFail($goodsList);
        }
        return $this->rQ($goodsList);
    }

    /**
     * 获取指定商品信息
     * @return array|string
     */
    public function goodsFind()
    {
        $goods = $this->getGoodsFind(
            ["g_id" => $this->data['g_id']],
            "g_id,g_name,gc_id,gc_name,model,pic,sku,bar_code,sku2,manufacturer,service_phone,performance,g_type,
            sell_channel,exter_url,is_gift,is_recommend,recoverable,heat,release_time,length,width,height,group_quantity,
            `status`,ao_id,update_time,`desc`,cost_price,market_price,retail_price",
            'update_time desc'
        );
        if (is_string($goods)) return $this->rFail($goods);
        if ($goods) {
            $goods = $goods->toArray();
            $goods['lang'] = $this->getGoodsLangList(['g_id' => $this->data['g_id']], 0, 'g_name,gc_name,pic,banner,details_pic,manufacturer,`desc`,performance,lang');
            $mg = $this->getMachineGoodsFind(['m_id' => $this->machine['m_id'], 'g_id' => $goods['g_id']], 'mg_id,available_stock,disabled_stock,cost_price,market_price,retail_price,reserve_stock,standby_stock,pre_loading_stock,is_shelf');
            if ($mg) $goods = array_merge($goods, $mg->toArray());
        }
        return $this->rQ($goods);
    }

    /**
     * 获取设备广告信息
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function adv()
    {
        $where['m_id'] = $this->machine['m_id'];
        $where[] = ['status', "<", 3];
        $where[] = ['start_date', '<=', time()];
        $field = "adv_id,adv_title,res_id,res_title,file_path,type,duration_time,total_times,play_times,remain_times,start_date,end_date,start_time,end_time,push_type,position,screen,screen_full,status";
        $advList = $this->getAdvertisementPushList($where, $this->data['pageNum'] ?? 0, $field);
        // 有效广告数为空时，发送微信模板消息（每天每设备限一次）
        if ($advList->isEmpty()) {
            $this->sendAdvEmptyNotice();
        }
        return $this->rQ($advList);
    }

    /**
     * 有效广告数为0时发送微信模板消息（复用mFault故障通知，错误码1002201）
     * 使用缓存限制每天每设备仅发送一次
     */
    private function sendAdvEmptyNotice()
    {
        $cacheKey = 'adv_empty_notice_' . $this->machine['m_id'];
        if (cache($cacheKey)) {
            return;
        }
        cache($cacheKey, 1, 7200);
        try {
            $this->message = [
                "errorCode" => '1002201',
                "msg" => '',
                "error_position" => '',
            ];
            $this->errorCode();
        } catch (\Exception $e) {
            actionException($e, 1);
        }
    }

    /**
     * 上报播放广告，adv_id, play_time
     * @return array|string
     */
    public function playAdv()
    {
        $where['adv_id'] = $this->data['adv_id'];
        $field = "adv_id,adv_title,res_id,res_title,type,type,duration_time,total_times,play_times,remain_times,m_id,machine_id,push_type,position,screen,screen_full,ao_id,download_progress";
        $adv = $this->getAdvertisementPushFind($where, $field);
        if (!$adv) return $this->rFail($this->lang("VAdvertisement.adv_no_data"));
        $adv = $adv->toArray();
        // 兜底：能请求此方法说明广告已能正常播放，若下载进度非100%则直接修正为100
        if (isset($adv['download_progress']) && $adv['download_progress'] != 100) {
            $adv['download_progress'] = 100;
        }
        if ($adv['total_times'] > 0) {
            if ($adv['remain_times'] > 0)
                $adv['remain_times']--;  // 剩余次数减1
            if ($adv['remain_times'] <= 0) {
                $adv['status'] = 3;
            }
        }
        $adv['play_times']++;    // 播放次数加1
        $insert = $adv;
        $this->startTrans();
        try {
            $flag[] = $this->updateAdvertisementPush($adv);
            //            actionLog($this->getLS(),'【SQL】修改广告播放计划');
            $insert['play_time'] = $this->data['play_time'];
            $flag[] = $this->addAdvertisementRecord($insert);
            //            actionLog($this->getLS(),'【SQL】添加广告播放记录');
            //            actionLog($flag,'记录结果集');
            $result = $this->checkFlag($flag);
            $check = $this->checkTrans($result, 0);
            if ($check) {
                return $this->r(200, $this->lang("VAdvertisement.adv_complete"), ['adv' => $adv]);
            }
            return $this->rFail($this->lang("action_fail"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 上报广告素材下载进度，百分比
     * @return array|\think\response\Json
     */
    public function reportAdvDownload()
    {
        $update['adv_id'] = $this->data['adv_id'];
        $update['download_progress'] = $this->data['download_progress'];
        $result = $this->updateAdvertisementPush($update);
        return $this->rU($result);
    }

    /**
     * 提交购物车生成订单信息
     * @return array|\think\response\Json
     */
    public function subCar()
    {
        //        if ($this->data['pay_type'] != 4 && $this->data['pay_type'] != 0) return $this->rFail($this->lang("VSubCar.pay_type_no_range"));
        if ($this->data['pay_method'] == "41") $this->data['pay_method'] = 1;
        $trade_no = date("YmdHis") . $this->machine['m_id'] . $this->get_rand_string(6, "num");
        if ($this->data['pay_type'] == 5 && (!isset($this->data['mobile']) || !$this->data['mobile'])) return $this->r(100, $this->lang("mobile_require"));
        $m_sel = [
            'm_id' => $this->machine['m_id']
        ];

        $m = $this->getMachineFind($m_sel, 'factory,inventory_location');

        $order = [
            "trade_no" => $trade_no,
            "m_id" => $this->machine['m_id'],
            "machine_name" => $this->machine['machine_name'],
            "machine_id" => $this->machine['machine_id'],
            //            "manager_id" => $this->machine['manager_id'],
            "ao_id" => $this->machine['ao_id'],
            "pay_type" => $this->data['pay_type'],
            "pay_method" => $this->data['pay_method'],
            "mobile" => $this->data['mobile'] ?? "",
            "create_date" => strtotime(date("Y-m-d")),
            "factory" => $m['factory'] ? $m['factory'] : '',
            "inventory_location" => $m['inventory_location'] ? $m['inventory_location'] : ''
        ];
        $updateOrder = [];
        $this->startTrans();
        try {
            $real_channel_code = 'Z10';
            $order_id = $this->addSaleOrders($order);
            if ($order_id) {
                $updateOrder['order_id'] = $order_id;
                $updateOrder['cost_price'] = 0;
                $updateOrder['market_price'] = 0;
                $updateOrder['retail_price'] = 0;
                $updateOrder['quantity'] = 0;
                $updateOrder['total_price'] = 0;
                $updateOrder['total_quantity'] = 0;
                $updateOrder['total_cost_points'] = 0;
                if (!isset($this->data['carList']) || !$this->data['carList']) {
                    $this->rollbackTrans();
                    return $this->rFail("购物车不能为空");
                }
                $this->data['carList'] = json2arr($this->data['carList']);
                //carList数据结构：
                //type=3 有早：[{"mc_id":186,"quantity":3,"channel_code":"Z10","out_no":"VC2507151411","no":"VC2507151415","order_date":["2026-03-07","2026-03-08","2026-03-09"]}]
                //type=3 无早：[{"mc_id":186,"quantity":4,"channel_code":"Z10","out_no":"VC2507151411","no":"VC2507151414","order_date":["2026-03-11","2026-03-07","2026-03-08","2026-03-09"]}]
                //type=3 有早+无早：[
                //     {"mc_id":186,"quantity":3,"channel_code":"Z10","out_no":"VC2507151411","no":"VC2507151415","order_date":["2026-03-07","2026-03-08","2026-03-09"]},
                //     {"mc_id":186,"quantity":4,"channel_code":"Z10","out_no":"VC2507151411","no":"VC2507151414","order_date":["2026-03-11","2026-03-07","2026-03-08","2026-03-09"]}
                // ]
                //type = 1：[{"mc_id":186,"quantity":3,"channel_code":"Z10","out_no":"VC2507151415","no":"VC2507151415","order_date":""}]
                //type = 11: [{"mc_id":186,"quantity":3,"channel_code":"Z10","out_no":"VC2507151415","no":"VC2507151415","order_date":""}]
                $total_sod_points = 0;
                foreach ($this->data['carList'] as $value) {
                    if (isset($value['channel_code']) && $value['channel_code'] == 'Z10') {
                        $wc_goods = $this->getWcGoodsFind(['no' => $value['out_no']]);
                        if ($wc_goods['maxBuy'] > 0 && $wc_goods['maxBuy'] < $value['quantity']) {
                            return $this->r(100, $this->lang("VSubCar.make_order_fail") . "：" . $wc_goods['name'] . "购买数量超过限购数量");
                        }
                        // todo 添加库存校验
                        $mc = $this->getWcMachineChannelFind(['mc_id' => $value['mc_id']]);
                        $mc['status'] = 1;
                        $wc_goods_locals = $this->getWcGoodsLocalList(['out_no' => $value['out_no']])->toArray();

                        $total_price = 0;
                        if ($wc_goods['type'] == 1) { //抢购商品没有子商品，直接根据父商品计算价格
                            $total_price = $wc_goods['price'];
                        } elseif ($wc_goods['type'] == 5) {
                            $now_wc_goods_locals = $this->getWcGoodsLocalList(['no' => $value['no'], 'out_no' => $value['out_no']])->toArray();
                            $total_price = $now_wc_goods_locals[0]['retail_price'] ?? 0;
                        } elseif ($wc_goods['type'] == 3) { //酒店房态商品时：此时carList传过来为单条记录
                            $now_wc_goods_locals = $this->getWcGoodsLocalList(['no' => $value['no'], 'out_no' => $value['out_no']])->toArray();
                            $daysInfo_json = $now_wc_goods_locals[0]['daysInfo'] ?? '';
                            $daysInfo = json_decode($daysInfo_json, true);
                            if (empty($value['order_date'])) {
                                $value['order_date'] = [date('Y-m-d')];
                            }
                            if (count($value['order_date']) ==  1) {
                                $total_price = $daysInfo[0]['price'] ?? 0;
                            } else {
                                $order_date = $value['order_date'];
                                array_pop($order_date);
                                foreach ($daysInfo as $vv) {
                                    if (in_array($vv['date'], $order_date)) {
                                        $total_price = bcadd($total_price, $vv['price'], 3);
                                    }
                                }
                            }
                        } elseif ($wc_goods['type'] == 11) { //组合||酒店房态商品时：此时carList传过来为单条记录
                            $now_wc_goods_locals = $this->getWcGoodsLocalList(['no' => $value['no'], 'out_no' => $value['out_no']])->toArray();
                            if ($now_wc_goods_locals[0]['isNeedReserve']) {
                                $total_price = $now_wc_goods_locals[0]['retail_price'];
                            } else {
                                $daysInfo_json = $now_wc_goods_locals[0]['daysInfo'] ?? '';
                                $daysInfo = json_decode($daysInfo_json, true);
                                if (empty($value['order_date'])) {
                                    $value['order_date'] = [date('Y-m-d')];
                                }
                                if (count($value['order_date']) ==  1) {
                                    $total_price = $daysInfo[0]['price'] ?? 0;
                                } else {
                                    $order_date = $value['order_date'];
                                    array_pop($order_date);
                                    foreach ($daysInfo as $vv) {
                                        if (in_array($vv['date'], $order_date)) {
                                            $total_price = bcadd($total_price, $vv['price'], 3);
                                        }
                                    }
                                }
                            }
                            $other_wc_goods_locals = $this->getWcGoodsLocalList([['no', '<>', $value['no']], 'out_no' => $value['out_no']])->toArray();
                            $total_price += array_sum(array_column($other_wc_goods_locals, 'retail_price')) ?? 0;
                        }
                        $wc_order_no = [];
                        foreach ($wc_goods_locals as $wc_goods_local) {
                            $wcLocalGid = $wc_goods_local['g_id'] ?? '9999';
                            $total_sod_points += $wc_goods_local['gift_points'];
                            if ($wcLocalGid != '9999' && $wcLocalGid != '0') {
                                $machine_channel = $this->getMachineChannelFind(['g_id' => $wcLocalGid, 'm_id' => $this->machine['m_id']]);
                                if ($machine_channel) {
                                    $machine_channel = $machine_channel->toArray();
                                    $real_channel_code = $machine_channel ? $machine_channel['channel_code'] : 'Z10';
                                }
                            }
                            $value['order_date'] = $value['order_date'] ?? [date('Y-m-d')];
                            $wc_order_no[$wc_goods_local['no']] = [
                                'out_no' => $value['out_no'], //父商品编码
                                'no' => $wc_goods_local['no'], //子商品编码
                                'order_no' => '', //订单同步时微程反馈的订单号
                                'order_date' => $value['order_date'], //房态商品订房日期
                                'quantity' => $value['quantity'] ?? 0, //微程商品数量
                                'total_price' => $total_price, //不同类型商品不同的价格
                                'need_local_out_goods' => $wcLocalGid ? 1 : 0, //是否需要本机出货  0-否 1-是
                                'out_goods_status' => 0, //出货状态  need_local_out_goods = 1时生效  0-未出货   1-已出货
                                'real_channel_code' => $real_channel_code, //实际出货货道
                                'total_sod_points' => $total_sod_points, //子订单微程商品赠送积分
                                // 'wc_user_address_id' => '', //微程会员寄送地址id
                                // 'wc_user_address' => '',//微程会员寄送详细地址
                            ];
                        }
                    } else {
                        $mc = $this->getMachineChannelFind(['mc_id' => $value['mc_id']]);
                    }
                    if (!$mc) {
                        $this->rollbackTrans();
                        return $this->r(300, $this->lang("VSubCar.channel_no_data"));
                    }
                    $mg = $this->getMachineGoodsFind(['mg_id' => $mc['mg_id']]);

                    if (!isset($value['channel_code']) && !$mc['mg_id']) {
                        $this->rollbackTrans();
                        return $this->r(300, $this->lang("VSubCar.mg_id_require"));
                    }
                    if (isset($value['channel_code']) && $value['channel_code'] != 'Z10' && $mc['status'] != 1) {
                        $this->rollbackTrans();
                        return $this->r(300, $this->lang("VSubCar.channel_status_no_3"));
                    }
                    if (isset($value['channel_code']) && $value['channel_code'] != 'Z10' &&  ($mc['stock'] < $value['quantity'])) {
                        $this->rollbackTrans();
                        return $this->r(300, $this->lang("VSubCar.under_stock"));
                    }
                    if ($this->data['pay_type'] == 0) {
                        $mc['retail_price'] = 0;
                    }
                    if (isset($value['channel_code']) && $value['channel_code'] == 'Z10') {
                        $quantity = $value['quantity'];
                        $foreach_quantity = 1;
                    } else {
                        $foreach_quantity = $value['quantity'];
                        $quantity = 1;
                    }
                    for ($i = 0; $i < $foreach_quantity; $i++) {
                        $details = [
                            "order_id" => $order_id,
                            "mc_id" => $mc['mc_id'],
                            "shelf_way" => $mc['shelf_way'] ?? 4, //4为虚拟货道
                            "channel_position" => $mc['channel_position'] ?? 3, //3为虚拟货道
                            "channel_code" => $mc['channel_code'],
                            "mg_id" => $mc['mg_id'] ?? 0,
                            "g_id" => $mc['g_id'],
                            "g_name" => $mc['g_name'],
                            "pic" => $mc['pic'],
                            "sku" => $mc['sku'],
                            "gc_id" => $mc['gc_id'],
                            "gc_name" => $mc['gc_name'],
                            "cost_price" => $mc['cost_price'] ?? 0,
                            "market_price" => $mc['market_price'] ?? 0,
                            "quantity" => $quantity,
                            "bar_code" => $mc['bar_code'] ?? '',
                            'total_sod_cost_points' => bcmul($mc['cost_points'], $quantity, 3),
                            'wc_order_no' => !empty($wc_order_no) ? json_encode($wc_order_no) : '', //微程商品信息
                            'sod_ao_id' => $mg['ao_id'] ?? '',
                        ];
                        $details['retail_price'] = !empty($wc_order_no) ? $total_price : $mc['retail_price'];
                        $details['total_sod_price'] = bcmul($details['retail_price'], $quantity, 3);
                        $sod_id = $this->addSaleOrdersDetails($details);

                        if ($sod_id) {
                            $updateOrder['cost_price'] = bcadd($updateOrder['cost_price'], bcmul($mc['cost_price'], $quantity, 2), 3);
                            $updateOrder['market_price'] = bcadd($updateOrder['market_price'], bcmul($mc['market_price'], $quantity, 2), 3);
                            $updateOrder['retail_price'] = bcadd($updateOrder['retail_price'], bcmul($mc['retail_price'], $quantity, 2), 3);
                            $updateOrder['quantity'] = bcadd($updateOrder['quantity'], $quantity);
                            $updateOrder['total_price'] = bcadd($updateOrder['total_price'], $details['total_sod_price'], 3);
                            $updateOrder['total_quantity'] = bcadd($updateOrder['total_quantity'], $quantity);
                            $updateOrder['total_cost_points'] = bcadd($updateOrder['total_cost_points'], $details['total_sod_cost_points'], 3);
                        } else {
                            $this->rollbackTrans();
                            return $this->r(300, $this->lang("VSubCar.make_order_details_fail"));
                        }
                    }
                }
                $this->commitTrans();
            } else {
                $this->rollbackTrans();
                return $this->r(300, $this->lang("VSubCar.make_order_fail"));
            }
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
        $this->startTrans();
        try {
            if ($updateOrder) {
                $updateOrder['retail_price'] = $updateOrder['total_price'];
                $flag[] = $this->updateSaleOrders($updateOrder);
                $this->order = $this->getSaleOrdersFind(['order_id' => $order_id]);
                $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $order_id], 0);
                actionLog($this->getLS(), '修改订单SQL');
                $result = $this->checkFlag($flag);
                actionLog($result, '事务结果');
                if ($result) {
                    // 免费的直接出货
                    if ($this->data['pay_type'] == 0) {
                        $this->rollbackTrans();
                        return $this->r(200, $this->lang("VSubCar.pay_type_empty"));
                        //                        $this->outGoods();
                        //                        $this->commitTrans();
                        //                        return $this->r(200, $this->lang("VSubCar.goods_outing"));
                    } else {
                        $this->commitTrans();
                        return $this->r(200, $this->lang("VSubCar.make_order_success"), ['order' => $this->order]);
                    }
                }
            }
            $this->rollbackTrans();
            return $this->r(300, $this->lang("VSubCar.make_order_fail"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 获取最新一条设备软件更新计划
     * @return array|string
     */
    public function machineVersionPlan()
    {
        $where['m_id'] = $this->machine['m_id'];
        $where[] = ['publish_time', "<", time()];
        $result = $this->getMachineVersionPlanFind($where, 'mvp_id,mv_id,version_no,path,`desc`,size,update_time,status', 'mvp_id desc');
        actionLog($result, '查询设备版本更新计划');
        actionLog($this->getLS(), '【SQL】查询设备版本更新计划');
        if (!$result) {
            return $this->rNoData();
        }
        if ($result['status'] != 1) return $this->rFail();
        return $this->rQ($result);
    }

    /**
     * 上报设备软件更新下载进度
     * @return array|\think\response\Json
     */
    public function machineVersionDownload()
    {
        $update['mvp_id'] = $this->data['mvp_id'];
        $update['download_progress'] = $this->data['download_progress'];
        $result = $this->updateMachineVersionPlan($update);
        return $this->rU($result);
    }

    /**
     * 获取库存盘点二维码
     * @return array|string
     */
    public function checkStockQrCode()
    {
        $params = [
            "timestamp" => time(),
            "machine_id" => $this->machine['machine_id'],
            "manager_id" => $this->data['manager_id'],
        ];
        $this->config['machine_id'] = $this->machine['machine_id'];
        $params['sign'] = $this->makeSign($params);
        $url = $this->host . "/mobile/#/index?" . http_build_query($params);
        return $this->rQ(['url' => $url]);
    }

    /**
     * 重置设备恢复出厂设置
     * 清除：货架信息、设备商品信息、广告推送、模板绑定关系
     * @return mixed
     */
    public function reset()
    {
        $where['m_id'] = $this->machine['m_id'];
        $this->delMachineChannel($where);
        $this->delMachineGoods($where);
        $this->delAdvertisementPush($where);
        $this->delMachineView($where);
        return $this->r(200, $this->lang("action_success"));
    }

    /**
     * 获取组合商品列表
     * 20241214修改接口内容，直对丽呈小程序查询组合商品列表接口
     * @return array|\think\response\Json
     */
    public function getGoodsMultiple()
    {
        //        try {
        //            $where['m_id'] = $this->machine['m_id'];
        //            $where['status'] = 1;
        //            $where[] = ['start_time', '<=', time()];
        //            $where[] = function ($query) {
        //                $query->where(" end_time is null or ( end_time > 0 AND end_time > " . time() . ")");
        //            };
        //            $field = "gm.gm_id,gm_name,gm_pic,gm_desc,start_time,end_time,status,m_id,machine_id,machine_name";
        //            $order = "gm_id desc";
        //            $data = $this->getGoodsMultipleListByMachine($where, 0, $field, $order);
        //            return $this->r(200, $this->lang("query_success"), $data);
        //        } catch (DbException $e) {
        //            actionException($e,1);
        //            return $this->rTryCatch($e->getMessage());
        //        }
        try {
            $params = [
                //                "pageSize" => $this->data['pageNum'] ?? 15,
                "pageSize" => 100,
                "pageNo" => $this->data['page'] ?? 1,
            ];
            if (isset($this->data['productSn']) && $this->data['productSn']) $params['productSn'] = $this->data['productSn'];
            $result = Trip::order()->getMallProductList($params);
            actionLog($result, "返回数据");
            $result = json2arr($result);
            $this->data['list'] = [];
            if (isset($result['result'])) {
                $this->data['list'] = $result['result'];
            }
            return $this->r(200, $this->lang("query_success"), $this->data);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 20241214废除此接口
     * 提交固定组合商品订单
     * @return array|\think\response\Json
     */
    public function subGoodsMultipleOrder()
    {
        return $this->r(100, '此接口已废除');
        $gm = $this->getGoodsMultipleFind(["gm_id" => $this->data['gm_id']]);
        if (!$gm) return $this->r(100, $this->lang("gm_not_data"));
        $gmm = $this->getGoodsMultipleMachineFind(['gm_id' => $this->data['gm_id'], 'm_id' => $this->machine['m_id']]);
        if (!$gmm) return $this->r(100, $this->lang("gmm_not_data"));

        if ($this->data['pay_method'] == "41") $this->data['pay_method'] = 1;
        $trade_no = date("YmdHis") . $this->machine['m_id'] . $this->get_rand_string(6, "num");
        if ($this->data['pay_type'] == 5 && (!isset($this->data['mobile']) || !$this->data['mobile'])) return $this->r(100, $this->lang("mobile_require"));
        $order = [
            "trade_no" => $trade_no,
            "m_id" => $this->machine['m_id'],
            "machine_name" => $this->machine['machine_name'],
            "machine_id" => $this->machine['machine_id'],
            //            "manager_id" => $this->machine['manager_id'],
            "ao_id" => $this->machine['ao_id'],
            "pay_type" => $this->data['pay_type'],
            "pay_method" => $this->data['pay_method'],
            "mobile" => $this->data['mobile'] ?? "",
            "gm_id" => $this->data['gm_id'],
            "goods_type" => 3,
            "create_date" => strtotime(date("Y-m-d")),
        ];
        $updateOrder = [];
        $this->startTrans();
        try {
            $order_id = $this->addSaleOrders($order);
            if ($order_id) {
                $this->order = $this->getSaleOrdersFind(['order_id' => $order_id]);
                $updateOrder['order_id'] = $order_id;
                $updateOrder['cost_price'] = 0;
                $updateOrder['market_price'] = 0;
                $updateOrder['retail_price'] = 0;
                $updateOrder['quantity'] = 0;
                $updateOrder['total_price'] = 0;
                $updateOrder['total_quantity'] = 0;
                if (!isset($this->data['carList']) || !$this->data['carList']) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VSubGoodsMultipleOrder.carList"));
                }
                $this->data['carList'] = json2arr($this->data['carList']);

                foreach ($this->data['carList'] as $key => $value) {
                    try {
                        validate(VReceive::class)->scene("carList")->check($value);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rFail($e->getMessage());
                    }
                    if (!isset($value['gmg_id']) || !$value['gmg_id']) {
                        $this->rollbackTrans();
                        return $this->rFail($this->lang("VSubGoodsMultipleOrder.gmg_id_require"));
                    }
                    // 查组合商品详情
                    $gmg = $this->getGoodsMultipleGoodsFind(['gmg_id' => $value['gmg_id']]);
                    if (!$gmg) {
                        $this->rollbackTrans();
                        return $this->r(100, $this->lang("VSubGoodsMultipleOrder.gmg_not_data"));
                    }
                    $gmg = $gmg->toArray();
                    $selling_price = bcmul($gmg['selling_price'], bcadd(1, bcdiv($gmg['rise_fall_ratio'], 100, 2), 2), 2);

                    $goods = $this->getGoodsFind(['g_id' => $value['g_id']], 'g_id,g_name,g_type,pic,sku,gc_id,gc_name,cost_price,market_price,retail_price,bar_code');
                    if ($goods['g_type'] == 2) {
                        $this->rollbackTrans();
                        return $this->r(100, $this->lang("VSubGoodsMultipleOrder.goods_type_error"));
                    }
                    $cost_price = $goods['cost_price'];
                    $market_price = $goods['market_price'];
                    $retail_price = $goods['retail_price'];
                    // 普通商品，需要匹配货道
                    if ($goods['g_type'] == 1) {
                        $mc = $this->getMachineChannelFind(['mc_id' => $value['mc_id']]);
                        if (!$mc) {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VSubCar.channel_no_data"));
                        }
                        if ($mc['status'] != 1) {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VSubCar.mc_status_error"));
                        }
                        if ($mc['stock'] < $value['quantity']) {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VSubCar.under_stock"));
                        }
                        if ($this->data['pay_type'] == 0) {
                            $mc['retail_price'] = 0;
                        }
                        $cost_price = $mc['cost_price'];
                        $market_price = $mc['market_price'];
                        $retail_price = $mc['retail_price'];
                    }
                    $sod_price = 0;
                    for ($i = 0; $i < $value['quantity']; $i++) {
                        $quantity = 1;
                        $details = [
                            "order_id" => $order_id,
                            "mc_id" => $mc['mc_id'] ?? 0,
                            "shelf_way" => $mc['shelf_way'] ?? null,
                            "channel_position" => $mc['channel_position'] ?? 1,
                            "channel_code" => $mc['channel_code'] ?? "",
                            "gmg_id" => $gmg['gmg_id'],
                            "mg_id" => $mc['mg_id'] ?? 0,
                            "g_id" => $goods['g_id'],
                            "g_name" => $goods['g_name'],
                            "g_type" => $goods['g_type'],
                            "pic" => $goods['pic'],
                            "sku" => $goods['sku'],
                            "gc_id" => $goods['gc_id'],
                            "gc_name" => $goods['gc_name'],
                            "cost_price" => $cost_price,
                            "market_price" => $market_price,
                            "retail_price" => $retail_price,
                            "total_sod_price" => bcmul($selling_price, $quantity, 2),
                            "quantity" => $quantity,
                            "bar_code" => $goods['bar_code'],
                        ];
                        $sod_id = $this->addSaleOrdersDetails($details);
                        if ($sod_id) {
                            $sod_price = bcadd($sod_price, $details['total_sod_price'], 2);
                            $updateOrder['cost_price'] = bcadd($updateOrder['cost_price'], bcmul($cost_price, $quantity, 2), 2);
                            $updateOrder['market_price'] = bcadd($updateOrder['market_price'], bcmul($market_price, $quantity, 2), 2);
                            $updateOrder['retail_price'] = bcadd($updateOrder['retail_price'], bcmul($retail_price, $quantity, 2), 2);
                            $updateOrder['quantity'] = bcadd($updateOrder['quantity'], $quantity);
                            $updateOrder['total_price'] = bcadd($updateOrder['total_price'], $details['total_sod_price'], 2);
                            $updateOrder['total_quantity'] = bcadd($updateOrder['total_quantity'], $quantity);
                        } else {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VAAAAAAAAAAAAAAAAAAAAAA.make_order_details_fail"));
                        }
                    }
                    if ($value['sod_price'] != $sod_price) {
                        $this->rollbackTrans();
                        return $this->r(100, $this->lang("VSubGoodsMultipleOrder.sod_price_not_eq"));
                    }
                }


                $this->data['hotel'] = json2arr($this->data['hotel']);
                if ($this->data['hotel']) {
                    try {
                        validate(VReceive::class)->scene("hotel")->check($this->data['hotel']);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rFail($e->getMessage());
                    }
                    $gmg = $this->getGoodsMultipleGoodsJoinGoodsFind(['gm_id' => $gm['gm_id'], 'g_type' => 2], 'gmg_id');
                    $updateOrder['has_hotel'] = 1;
                    $updateOrder['total_price'] = bcadd($updateOrder['total_price'], bcdiv($this->data['hotel']['pay_amount'], 100, 2), 2);
                    $insertHotel = [
                        "order_id" => $this->order['order_id'],
                        "m_id" => $this->order['m_id'],
                        "machine_id" => $this->order['machine_id'],
                        "machine_name" => $this->order['machine_name'],
                        "gm_id" => $this->data['gm_id'] ?? 0,
                        "gmg_id" => $gmg['gmg_id'] ?? 0,
                        "hotel_trade_no" => "",
                        "hotelId" => $this->data['hotel']['hotelId'] ?? null,
                        "hotelFrom" => 2,
                        "roomId" => $this->data['hotel']['roomId'] ?? null,
                        "num" => $this->data['hotel']['num'],
                        "adults" => $this->data['hotel']['adults'],
                        "totalPrice" => $this->data['hotel']['totalPrice'],
                        "mobile" => $this->data['mobile'],
                        "pay_amount" => $this->data['hotel']['pay_amount'],
                        "checkInDate" => $this->data['hotel']['checkInDate'],
                        "checkOutDate" => $this->data['hotel']['checkOutDate'],
                        "guestNames" => $this->data['hotel']['guestNames'] ?? "",
                    ];
                    $sh_id = $this->addSaleHotel($insertHotel);
                    if (!$sh_id) {
                        $this->rollbackTrans();
                        return $this->r(100, $this->lang("VSubCar.make_sale_hotel_fail"));
                    }
                    if ($this->data['hotel']['pay_amount'] != array_sum(array_column($this->data['hotel']['roomPriceList'], 'amount'))) {
                        $this->rollbackTrans();
                        return $this->r(100, $this->lang("VSubGoodsMultipleOrder.hotel_amount_not_eq_total_room_price"));
                    }
                    foreach ($this->data['hotel']['roomPriceList'] as $nk => $nv) {
                        try {
                            validate(VReceive::class)->scene("nightly")->check($nv);
                        } catch (\Exception $e) {
                            $this->rollbackTrans();
                            return $this->rFail($e->getMessage());
                        }
                        $insertN = [
                            "sh_id" => $sh_id,
                            "hotelId" => $this->data['hotel']['hotelId'] ?? null,
                            "roomId" => $this->data['hotel']['roomId'] ?? null,
                            "effectiveDate" => $nv['effectiveDate'],
                            "amount" => $nv['amount'],
                        ];
                        $sn_id = $this->addSaleHotelNightly($insertN);
                        if (!$sn_id) {
                            $this->rollbackTrans();
                            return $this->r(100, $this->lang("VSubCar.make_hotel_nightly_fail"));
                        }
                    }
                }
                $this->commitTrans();
            } else {
                $this->rollbackTrans();
                return $this->r(100, $this->lang("VSubCar.make_order_fail"));
            }
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
        $this->startTrans();
        try {
            if ($updateOrder) {
                if ($this->data['total_price'] != $updateOrder['total_price']) {
                    $this->rollbackTrans();
                    return $this->r(100, $this->lang("VSubGoodsMultipleOrder.total_price_not_eq"), ['calculate_price' => $updateOrder['total_price'], 'total_price' => $this->data['total_price']]);
                }
                $updateOrder['retail_price'] = $updateOrder['total_price'];
                $flag[] = $this->updateSaleOrders($updateOrder);
                actionLog($this->getLS(), '修改订单SQL');
                $result = $this->checkFlag($flag);
                actionLog($result, '事务结果');
                if ($result) {
                    $this->commitTrans();
                    $field = "order_id,trade_no,out_trade_no,mobile,order_type,pay_status,pay_type,pay_method,cost_price,market_price,total_price,total_quantity,has_hotel,goods_type,create_time";
                    $this->order = $this->getSaleOrdersFind(['order_id' => $this->order['order_id']], $field);
                    $detailsField = "sod_id,order_id,mc_id,channel_position,channel_code,mg_id,
                    g_id,g_name,g_type,pic,sku,gc_name,cost_price,market_price,retail_price,discount_price,total_sod_price,quantity,bar_code,batch_number,is_gift";
                    $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']], 0, $detailsField);
                    if ($this->order['has_hotel'] == 1) {
                        $hotelField = "sh_id,order_id,hotelId,hotelFrom,roomId,logId,tripData,totalPrice,mobile,num,adults,checkInDate,checkOutDate,guestNames,expectCheckInTime,pay_amount,reservation_status,create_status,create_time";
                        $this->order['hotelList'] = $this->getSaleHotelFind(["order_id" => $this->order['order_id']], $hotelField);
                        if ($this->order['hotelList']) {
                            $this->order['hotelList']['nightList'] = $this->getSaleHotelNightlyList(
                                ['sh_id' => $this->order['hotelList']['sh_id']],
                                0,
                                'sn_id,sh_id,hotelId,roomId,effectiveDate,amount'
                            );
                        }
                    }
                    // 免费的直接出货
                    //                    if ($this->data['pay_type'] == 0) {
                    ////                        $this->outGoods();
                    //                        $this->commitTrans();
                    //                        return $this->r(200, $this->lang("VSubCar.goods_outing"));
                    //                    } else {
                    return $this->r(200, $this->lang("VSubCar.make_order_success"), ['order' => $this->order]);
                    //                    }
                }
            }
            $this->rollbackTrans();
            return $this->r(100, $this->lang("VSubCar.make_order_fail"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 设备退出H5页面
     * @return array|bool|string
     */
    public function logoutH5()
    {
        return $this->sendToMachine($this->machine, 'logoutH5');
    }

    /**
     * 小票打印文本
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function receipt()
    {
        $order = $this->getSaleOrdersFind(
            ['order_id' => $this->data['order_id']],
            'order_id,trade_no,mch_no,fd_id,coupon_id,m_id,machine_id,machine_name,total_quantity,discount_price,retail_price,total_price,total_points,pay_type,pay_method'
        );
        $order = $order->toArray();
        actionLog($order, '订单数据');
        $mConfig = $this->getMachineConfigFind(['m_id' => $order['m_id']], 'receipt_code1,receipt_code2,receipt_code3,receipt_desc,deal_service_phone,receipt_code1_desc,receipt_code2_desc');
        $pIds = $this->getAuthManagerMachineColumn(['m_id' => $this->machine['m_id']], 'manager_id');
        $pIds = array_merge($pIds, $this->getParentIdList($this->machine['creator']));
        $pIds[] = $this->machine['creator'];
        $pIds[] = 1;
        $systemInfo = $this->getConfigContent([['creator', 'in', $pIds], "config_switch" => 1, 'config_name' => "systemInfo"]);
        if (strpos($this->machine['logo'], 'http') === false) {
            $this->machine['logo'] = $systemInfo['domain_name'] . $this->machine['logo'];
        }
        $mConfig['receipt_code1'] = empty($mConfig['receipt_code1']) ? $this->receipt_code1 : $mConfig['receipt_code1'];
        $mConfig['receipt_code2'] = empty($mConfig['receipt_code2']) ? $this->receipt_code2 : $mConfig['receipt_code2'];
        if ($mConfig['receipt_code1'] && strpos($mConfig['receipt_code1'], 'http') === false) {
            $mConfig['receipt_code1'] = $systemInfo['domain_name'] . $mConfig['receipt_code1'];
        }
        if ($mConfig['receipt_code2'] && strpos($mConfig['receipt_code2'], 'http') === false) {
            $mConfig['receipt_code2'] = $systemInfo['domain_name'] . $mConfig['receipt_code2'];
        }
        if ($mConfig['receipt_code3'] && strpos($mConfig['receipt_code3'], 'http') === false) {
            $mConfig['receipt_code3'] = $systemInfo['domain_name'] . $mConfig['receipt_code3'];
        }
        $ac_name = [];
        if ($order['fd_id'] > 0) $ac_name[] = "满减";
        if ($order['coupon_id'] > 0) $ac_name[] = "优惠券";
        $mch_no = "";
        if (isset($order['mch_no']) && $order['mch_no']) {
            $mch_no = substr($order['mch_no'], 0, 10) . "****" . substr($order['mch_no'], -4);
        }
        $this->getMachineAddress();
        $name = "cname";
        if ($this->machine['lang'] != "zh-cn") {
            $name = "name";
        }
        $address = [];
        if (isset($this->machine['country'][$name]) && $this->machine['country'][$name]) $address[] = $this->machine['country'][$name];
        if (isset($this->machine['state'][$name]) && $this->machine['state'][$name]) $address[] = $this->machine['state'][$name];
        if (isset($this->machine['city'][$name]) && $this->machine['city'][$name]) $address[] = $this->machine['city'][$name];
        if (isset($this->machine['regions'][$name]) && $this->machine['regions'][$name]) $address[] = $this->machine['regions'][$name];
        if (isset($this->machine['street']) && $this->machine['street']) $address[] = $this->machine['street'];
        if (isset($this->machine['floor']) && $this->machine['floor']) $address[] = $this->machine['floor'];
        $data = [
            "logo"           => $this->machine['logo'],
            'machine_id'   => $order['machine_id'],
            'machine_name'   => $order['machine_name'],
            'address' => implode("", $address),
            'print_date'     => date("Y-m-d"),
            'print_time'     => date("H:i:s"),
            'trade_no'     => $order['trade_no'],
            'mch_no'     => $mch_no,
            'currency' => $this->machine['currency'],
            'detailsList'    => $this->getSaleOrdersDetailsList(
                ['order_id' => $order['order_id']],
                0,
                'g_name,quantity,retail_price,is_gift,discount_price,total_sod_price,total_sod_points'
            )->toArray(),
            'total_quantity' => $order['total_quantity'],
            'discount_price' => $order['discount_price'],
            'retail_price' => number_format($order['retail_price'], 2),
            'total_price'    => number_format($order['total_price'], 2),
            'total_points' => $order['total_points'],
            'ac_name' => implode("/", $ac_name),
            'pay_type' => $this->formatPayType($order['pay_type'] ?? 0) . (($order['pay_method'] ?? 0) > 0 ? "-" . $this->formatPayMethod($order['pay_method']) : ""),
            'service_tel'    => $mConfig['deal_service_phone'],
            'receipt_code1'  => $mConfig['receipt_code1'],
            'receipt_code2'  => $mConfig['receipt_code2'],
            'receipt_code3'  => $mConfig['receipt_code3'],
            'receipt_desc'   => $mConfig['receipt_desc'],
            'receipt_code1_desc'  => empty($mConfig['receipt_code1_desc']) ? '客服微信' : $mConfig['receipt_code1_desc'],
            'receipt_code2_desc'  => empty($mConfig['receipt_code2_desc']) ? '在线商城' : $mConfig['receipt_code2_desc'],
        ];
        if (in_array(1, array_column($data['detailsList'], 'is_gift')))
            $data['ac_name'] = $this->lang("gift");
        actionLog($data, '小票数据');
        View::assign($data);
        $result = View::fetch("receipt/print2");
        actionLog($result, '小票文本');
        $this->updateSaleOrders(['order_id' => $this->data['order_id'], 'receipt' => $result]);
        return $this->r(200, 'success', ['receipt' => $result]);
    }



    /**
     * 设备上报回收箱信息
     */
    public function recycleBoxReport()
    {
        $this->updateMachine([
            "recycle_box_total_capacity" => $this->machine['recycle_box_total_capacity'] ?? 0,
            "recycle_box_remain_capacity" => $this->machine['recycle_box_remain_capacity'] ?? 0
        ], [
            'machine_id' => $this->machine['machine_id'] ?? 0
        ]);
        return $this->r(200, 'success');
    }

/**
     * HTTP 心跳上报
     * 与 MQ 心跳类似：更新 last_online_time、online；并单独维护 http_online（仅 HTTP 通道，与 machine.online 可区分）。
     * 若 5 分钟内存在重启指令，则直接回传重启命令给设备。
     *
     * @return array|string
     */
    public function httpHeartbeat()
    {
        $update = [
            'online' => 1,
            'http_online' => 1,
            'last_online_time' => time(),
        ];
        if (isset($this->data['version']) && $this->data['version']) {
            $update['version'] = $this->data['version'];
        }
        $where = ['m_id' => $this->machine['m_id']];
        actionLog(
            [
                'machine_id' => $this->machine['machine_id'] ?? '',
                'm_id' => $this->machine['m_id'] ?? 0,
                'msgType' => $this->data['msgType'] ?? null,
                'db_http_online_before' => $this->machine['http_online'] ?? null,
                'update' => $update,
                'where' => $where,
            ],
            'HTTP心跳即将更新 machine（online/http_online/last_online_time）',
            'httpHeartbeat'
        );
        $this->updateMachine($update, $where);
        actionLog($this->getLS(), 'HTTP心跳执行 updateMachine SQL', 'httpHeartbeat');

        $restartCommand = $this->getRecentRestartCommand(300);
        if ($restartCommand) {
            $restart = [
                'msgType' => $restartCommand['msgType'],
                'msg_id' => $restartCommand['msg_id'] ?? '',
                'timestamp' => intval($restartCommand['timestamp'] ?? 0),
            ];
            if (!empty($restartCommand['field'])) {
                $restart['field'] = $restartCommand['field'];
            }
            return $this->r(200, 'success', [
                'has_restart_command' => 1,
                'restart' => $restart,
            ]);
        }

        return $this->r(200, 'success', [
            'has_restart_command' => 0,
        ]);
    }

    /**
     * HTTP接收设备上传的首页截屏。
     * 设备先上传文件拿到路径后，再通过该接口把截图路径写入 machine_info.screen_img。
     * @return array|string
     */
    public function reportScreenImg()
    {
        $path = $this->data['path'] ?? ($this->data['screen_img'] ?? '');
        if (!$path) {
            return $this->rValidate('图片路径不能为空');
        }

        $this->message = [
            'msgType' => 'img',
            'field' => 'screen_img',
            'path' => $path,
        ];
        $result = $this->img();
        actionLog(['data' => $this->data, 'result' => $result], 'HTTP首页截屏回传结果', 'reportScreenImg');
        if ($result === false) {
            return $this->r(300, '首页截屏保存失败');
        }

        $commandMsgId = $this->data['command_msg_id'] ?? ($this->data['request_msg_id'] ?? '');
        if ($commandMsgId) {
            $this->updateMachineMqRecord(
                ['status' => 4],
                ['machine_id' => $this->machine['machine_id'], 'msg_id' => $commandMsgId],
                ['status']
            );
        }

        return $this->r(200, 'success', [
            'screen_img' => $path,
        ]);
    }

    /**
     * 获取最近指定时间内的重启指令
     * @param int $seconds
     * @return array|null
     */
    protected function getRecentRestartCommand($seconds = 300)
    {
        $rows = Db::name('machine_mq_record')
            ->where('machine_id', $this->machine['machine_id'])
            ->where('type', 2)
            ->where('create_time', '>=', time() - intval($seconds))
            ->order('mr_id', 'desc')
            ->limit(20)
            ->select()
            ->toArray();

        if (!$rows) {
            return null;
        }

        $restartTypes = ['reboot','powerWakeUp'];
        foreach ($rows as $row) {
            $record = json2arr($row['content'] ?? '');
            if (!$record) {
                continue;
            }
            $payload = json2arr($record['data'] ?? '');
            if (!$payload || !isset($payload['msgType'])) {
                continue;
            }
            if ($payload['msgType'] === 'img' && ($payload['field'] ?? '') === 'screen_img') {
                return [
                    'msgType' => $payload['msgType'],
                    'field' => $payload['field'],
                    'msg_id' => $record['msg_id'] ?? ($row['msg_id'] ?? ''),
                    'timestamp' => $record['timestamp'] ?? ($row['create_time'] ?? 0),
                ];
            }
            if (in_array($payload['msgType'], $restartTypes, true)) {
                return [
                    'msgType' => $payload['msgType'],
                    'msg_id' => $record['msg_id'] ?? ($row['msg_id'] ?? ''),
                    'timestamp' => $record['timestamp'] ?? ($row['create_time'] ?? 0),
                ];
            }
        }

        return null;
    }

    public function requireOutGoods()
    {
        $order = $this->getSaleOrdersFind(['trade_no' => $this->data['trade_no']]);
        if (!$order) return $this->r(300, $this->lang("VSaleOrders.order_not_data"));
        $this->order = is_object($order) ? (method_exists($order,'toArray') ? $order->toArray() : (array)$order) : $order;
        $details = $this->order['details'] ?? $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']])->toArray();
        if (!$details || !is_array($details)) {
            return $this->r(100, 'failed', []);
        }
        $now_time = time();
        $contentArr = [];
        $outArr = [];
        $total_points = 0;
        $can_out_goods = true;

        if($now_time - $this->order['pay_time'] > 180){
            $can_out_goods = false;
            $content = [
                'msgType' => 'outGoods',
                'trade_no' => $this->order['trade_no'],
                'main' => $contentArr,
                'outGoods' => $outArr,
                'order_points' => $this->order['total_points'] ?? 0,
                'can_out_goods' => $can_out_goods,
            ];
            return $this->r(200, 'success', $content);
        }
        

        // 支持旧数据格式：把简单 channel/quantity 填到 contentArr
        foreach ($details as $v) {
            if (!$v['mc_id']) $v['g_type'] = 1;
                if ($v['g_type'] == 1) {
                    $dc = [
                        $v['channel_code'],
                        $v['quantity'],
                    ];
                    $contentArr[$v['channel_position']][] = $dc;
                }
        }

        // 新数据格式：逐条构建 out payload，并安全计算积分
        foreach ($details as $v) {
            // ensure sod_id present
            $updateSod = ['sod_id' => $v['sod_id'] ?? 0];
            $updateSod['total_sod_points'] = 0;
            $updateSod['intergral_rate'] = $updateSod['intergral_rate'] ?? 0;

            // If no mc_id, treat as simple channel out
            if (empty($v['mc_id'])) {
                $pos = $v['channel_position'] ?? 0;
                $outArr[$pos][] = [
                    'channel_code' => $v['channel_code'] ?? '',
                    'quantity' => $v['quantity'] ?? 1,
                    'is_gift' => $v['is_gift'] ?? 2,
                    'out_port' => $v['out_port'] ?? 1,
                ];
                // persist update if needed (points or checkoff handled below)
                $this->updateSaleOrdersDetails($updateSod);
                continue;
            }

            // load channel record safely
            if (($v['channel_code'] ?? '') === 'Z10') {
                $mcModel = $this->getWcMachineChannelFind(['mc_id' => $v['mc_id']]);
            } else {
                $mcModel = $this->getMachineChannelFind(['mc_id' => $v['mc_id']]);
            }
            $mc = null;
            if ($mcModel) {
                $mc = is_object($mcModel) ? (function_exists('obj2arr') ? obj2arr($mcModel) : (array)$mcModel) : $mcModel;
            }
            $mc = is_array($mc) ? $mc : [];

            // compute points safely
            $rate_points = $this->getRateOrGiftPoints($mc);
            $gift_points = $rate_points['gift_points'] ?? 0;
            $intergral_rate = $rate_points['intergral_rate'] ?? 0;

            if ($gift_points > 0) {
                $updateSod['intergral_rate'] = 0;
                $updateSod['total_sod_points'] = $gift_points * ($v['quantity'] ?? 1);
            } elseif ($intergral_rate) {
                $updateSod['intergral_rate'] = $intergral_rate;
                $sod_price = $v['total_sod_price'] ?? 0;
                $updateSod['total_sod_points'] = bcmul($sod_price, $intergral_rate, 3);
            }

            $total_points += (float)($updateSod['total_sod_points'] ?? 0);

            // 减库存（固定组合商品）——尽量捕获异常，避免因一次失败导致整个接口崩溃
            if (($v['g_type'] ?? 0) != 1 && !empty($v['gmg_id'])) {
                try {
                    $flag[] = $this->setGoodsMultipleGoodsDec(['gmg_id' => $v['gmg_id']], 'stock');
                    actionLog($this->getLS(), '减固定组合商品酒店库存');
                } catch (\Exception $e) {
                    actionException($e);
                }
            }

            // 普通出货项，构建出货列表
            if (($v['g_type'] ?? 0) == 1) {
                $pos = $v['channel_position'] ?? 0;
                $wcLocalOutGoods = $this->resolveWcLocalOutGoodsItems($v, $mc);
                if ($wcLocalOutGoods) {
                    foreach ($wcLocalOutGoods as $item) {
                        $outArr[$pos][] = $item;
                    }
                } else {
                    $outItem = [
                        'channel_code' => $v['channel_code'] ?? '',
                        'quantity' => $v['quantity'] ?? 1,
                        'is_gift' => $v['is_gift'] ?? 2,
                        'out_port' => $v['out_port'] ?? 1,
                    ];
                    $outArr[$pos][] = $outItem;
                    // 如果是微程组合商品（type==11），尝试解析子商品出货信息
                    if (!empty($mc['out_no'])) {
                        $wcGoodsModel = $this->getWcGoodsFind(['no' => $mc['out_no']]);
                        if ($wcGoodsModel) {
                            $wcGoods = is_object($wcGoodsModel) ? (function_exists('obj2arr') ? obj2arr($wcGoodsModel) : (array)$wcGoodsModel) : $wcGoodsModel;
                            if (($wcGoods['type'] ?? 0) == 11 && !empty($v['wc_goods_no'])) {
                                $wc_goods_no_arr = json_decode($v['wc_goods_no'], true);
                                if (is_array($wc_goods_no_arr)) {
                                    foreach ($wc_goods_no_arr as $wc_goods_no_v) {
                                        $dc_local = [
                                            'channel_code' => $wc_goods_no_v['real_channel_code'] ?? '',
                                            'quantity' => 1,
                                            'is_gift' => $v['is_gift'] ?? 2,
                                            'out_port' => $v['out_port'] ?? 1,
                                        ];
                                        $outArr[$pos][] = $dc_local;
                                    }
                                } else {
                                    actionLog(['sod_id' => $v['sod_id'] ?? 0, 'wc_goods_no' => $v['wc_goods_no']], 'wc_goods_no JSON parse failed');
                                }
                            }
                        }
                    }
                }
            }

            if (($v['g_type'] ?? 0) == 3) {
                // 获取核销码
                $updateSod['checkOff_code'] = $this->getDetailsCheckOffCode();
            }

            // 更新自订单信息（积分/核销码等）——这里尽量保持原子并捕获异常
            try {
                $this->updateSaleOrdersDetails($updateSod);
            } catch (\Exception $e) {
                actionException($e);
            }
        }

        if ($total_points) {
            $this->order['total_points'] = $total_points;
        }

        $contentArr = $this->buildLegacyMainFromOutGoods($outArr);

        $content = [
            'msgType' => 'outGoods',
            'trade_no' => $this->order['trade_no'],
            'main' => $contentArr,
            'outGoods' => $outArr,
            'order_points' => $this->order['total_points'] ?? 0,
            'can_out_goods' => $can_out_goods,
         ];

        $this->order['out_status'] = 2;
        try {
            $this->updateSaleOrders($this->order);
        } catch (\Exception $e) {
            actionException($e);
        }

        return $this->r(200, 'success', $content);
    }

    /**
     * 微程实物商品(type=5)和组合商品(type=11)按本机实际货道出货。
     */
    protected function resolveWcLocalOutGoodsItems($detail, array $mc): array
    {
        // normalize $detail to array when an object or model is passed
        if (is_object($detail)) {
            $detail = method_exists($detail, 'toArray') ? $detail->toArray() : (array)$detail;
        } elseif (!is_array($detail)) {
            $detail = (array)$detail;
        }

        // ensure $mc is array
        if (is_object($mc)) {
            $mc = method_exists($mc, 'toArray') ? $mc->toArray() : (array)$mc;
        }
        if (empty($mc['out_no'])) {
            return [];
        }

        $wcGoodsModel = $this->getWcGoodsFind(['no' => $mc['out_no']]);
        if (!$wcGoodsModel) {
            return [];
        }
        $wcGoods = is_object($wcGoodsModel) ? (function_exists('obj2arr') ? obj2arr($wcGoodsModel) : (array)$wcGoodsModel) : $wcGoodsModel;
        $wcGoodsType = intval($wcGoods['type'] ?? 0);
        if (!in_array($wcGoodsType, [5, 11], true)) {
            return [];
        }

        $localGoods = [];
        if (!empty($detail['wc_goods_no'])) {
            $decoded = json_decode($detail['wc_goods_no'], true);
            if (is_array($decoded)) {
                $localGoods = array_values($decoded);
            } else {
                actionLog([
                    'sod_id' => $detail['sod_id'] ?? 0,
                    'wc_goods_no' => $detail['wc_goods_no'],
                ], 'wc_goods_no JSON parse failed');
            }
        }

        if (!$localGoods) {
            $localWhere = ['out_no' => $mc['out_no']];
            if ($wcGoodsType === 5) {
                $detailGId = intval($detail['g_id'] ?? 0);
                if ($detailGId > 0 && $detailGId !== 9999) {
                    $localWhere['g_id'] = $detailGId;
                } elseif (!empty($detail['sku'])) {
                    $localWhere['sku'] = $detail['sku'];
                } elseif (!empty($detail['bar_code'])) {
                    $localWhere['bar_code'] = $detail['bar_code'];
                }
            }
            $wcGoodsLocal = $this->getWcGoodsLocalList($localWhere);
            if ($wcGoodsLocal) {
                $localGoods = $wcGoodsLocal->toArray();
            }
            if ($wcGoodsType === 5 && count($localGoods) > 1) {
                actionLog([
                    'sod_id' => $detail['sod_id'] ?? 0,
                    'out_no' => $mc['out_no'],
                    'local_count' => count($localGoods),
                ], '微程实物商品匹配到多个本地商品，已跳过实际货道兜底');
                return [];
            }
        }

        $items = [];
        foreach ($localGoods as $local) {
            $needLocalOutGoods = intval($local['need_local_out_goods'] ?? 1);
            if ($needLocalOutGoods !== 1) {
                continue;
            }

            $channelCode = trim((string)($local['real_channel_code'] ?? ''));
            if ($channelCode === '' || $channelCode === 'Z10') {
                $gId = intval($local['g_id'] ?? 0);
                if ($gId > 0 && $gId !== 9999) {
                    $machineChannel = $this->getMachineChannelFind([
                        'g_id' => $gId,
                        'm_id' => intval($this->order['m_id'] ?? 0),
                    ], 'channel_code');
                    if ($machineChannel) {
                        $machineChannel = is_array($machineChannel) ? $machineChannel : obj2arr($machineChannel);
                        $channelCode = trim((string)($machineChannel['channel_code'] ?? ''));
                    }
                }
            }

            if ($channelCode === '' || $channelCode === 'Z10') {
                actionLog([
                    'sod_id' => $detail['sod_id'] ?? 0,
                    'out_no' => $mc['out_no'],
                    'local_no' => $local['no'] ?? '',
                    'g_id' => $local['g_id'] ?? 0,
                ], '微程商品未匹配到实际货道');
                continue;
            }

            $items[] = [
                'channel_code' => $channelCode,
                'quantity' => intval($local['quantity'] ?? ($detail['quantity'] ?? 1)),
                'is_gift' => $detail['is_gift'] ?? 2,
                'out_port' => $detail['out_port'] ?? 1,
            ];
        }

        return $items;
    }

    protected function buildLegacyMainFromOutGoods(array $outArr): array
    {
        $main = [];
        foreach ($outArr as $position => $items) {
            foreach ($items as $item) {
                $channelCode = $item['channel_code'] ?? '';
                if ($channelCode === '') {
                    continue;
                }
                $main[$position][] = [
                    $channelCode,
                    intval($item['quantity'] ?? 1),
                ];
            }
        }
        return $main;
    }
    
    /**
     * HTTP触发出货结果闭环：将出货回执投递到MQ，触发 OutGoodsTrait::outGoods
     * 适用：仅调用 requireOutGoods 后，设备未通过MQ回传出货结果的场景
     * @return array|string
     */
    public function triggerOutGoodsByHttp()
    {
        $tradeNo = $this->data['trade_no'] ?? '';
        $status = isset($this->data['http_out_status']) ? intval($this->data['http_out_status']) : 0;
        $machine_id = $this->data['machine_id'] ??  '';
        if (!in_array($status, [1, 2, 20, 21, 3, 4], true)) {
            return $this->r(300, 'http_out_status参数无效');
        }

        $order = $this->getSaleOrdersFind(['trade_no' => $tradeNo], 'order_id,trade_no,machine_id,out_status');
        if (!$order) {
            return $this->r(300, $this->lang("VSaleOrders.order_not_data"));
        }
        $order = is_object($order) ? (method_exists($order, 'toArray') ? $order->toArray() : (array)$order) : $order;
        if (!$machine_id) {
            $machine_id = $order['machine_id'] ?? '';
        }

        // 已完成出货，避免重复闭环
        if ((int)($order['out_status'] ?? 0) === 4) {
            return $this->r(200, 'success', [
                'trade_no' => $tradeNo,
                'skip' => 1,
                'reason' => 'order out_status already 4',
            ]);
        }

        $main = $this->data['main'] ?? [];
        if (is_string($main) && $main !== '') {
            $main = json2arr($main);
        }
        if (!is_array($main)) {
            $main = [];
        }

        // 未传main时按订单明细自动构建“全成功”回执，确保能触发 outGoods 完整处理
        if (empty($main) && in_array($status, [3, 4], true)) {
            $details = $this->getSaleOrdersDetailsList(['order_id' => $order['order_id']], 0, 'channel_position,channel_code,quantity');
            $details = $details ? (is_object($details) ? $details->toArray() : (array)$details) : [];
            foreach ($details as $row) {
                $position = $row['channel_position'] ?? 0;
                $channelCode = $row['channel_code'] ?? '';
                if ($channelCode === '') {
                    continue;
                }
                $quantity = intval($row['quantity'] ?? 0);
                if ($quantity <= 0) {
                    continue;
                }
                $main[$position][] = [
                    'channel_code' => $channelCode,
                    'success_quantity' => $quantity,
                    'fail_quantity' => 0,
                    'deliver_pics' => '',
                    'out_sequence' => 1,
                ];
            }
        }

        // 与 OutGoodsTrait 规则对齐：status=3/4 时必须有 main 主体数据
        if (empty($main) && in_array($status, [3, 4], true)) {
            return $this->r(300, '主体数据不能为空');
        }

        $payload = [
            'msgType' => 'outGoods',
            'trade_no' => $tradeNo,
            'status' => $status,
            'main' => $main,
        ];
        $machine = $this->getMachineFind(['machine_id' => $machine_id], 'machine_id,mac_address');
        if (!$machine) {
            return $this->r(300, $this->lang("query_machine_no_data"));
        }
        $mqData = [
            'msg_id' => $this->data['msg_id'] ?? uniqid('http_out_', true),
            'timestamp' => time(),
            'machine_id' => $machine_id,
            'mac' => $machine['mac_address'] ?? '',
            'data' => json_encode($payload, 320),
        ];

        // 默认同步触发，确保即使MQ消费者未运行也能进入 OutGoodsTrait::outGoods。
        // $sync = isset($this->data['sync']) ? intval($this->data['sync']) : 1;
        // $syncResult = null;
        // if ($sync === 1) {
        //     try {
        //         $config = [
        //             'machine_id' => $mqData['machine_id'],
        //             'data' => $mqData,
        //             'mac' => $mqData['mac'],
        //         ];
        //         $syncResult = AppFactory::machine($config)->mq->onMessage();
        //         actionLog(['mqData' => $mqData, 'syncResult' => $syncResult], 'HTTP同步触发出货闭环');
        //     } catch (\Exception $e) {
        //         actionException($e, 1, 'triggerOutGoodsByHttp-sync');
        //         return $this->rTryCatch($e->getMessage());
        //     }
        // }
        $config = [
            'machine_id' => $mqData['machine_id'],
            'data' => $mqData,
            'mac' => $mqData['mac'],
        ];
        $syncResult = AppFactory::machine($config)->mq->onMessage();
        actionLog(['mqData' => $mqData, 'syncResult' => $syncResult], 'HTTP同步触发出货闭环');
        // 可选入队：默认关闭，避免同步执行后再次被队列重复消费。
        // $enqueue = isset($this->data['enqueue']) ? intval($this->data['enqueue']) : 0;
        // $push = true;
        // if ($enqueue === 1) {
            // $push = MqProducer::dataUpload($mqData);
            // actionLog(['mqData' => $mqData, 'result' => $push], 'HTTP触发MQ出货闭环(入队)');
        // }

        // if ($enqueue === 1 && $push !== 'OK' && $push !== true) {
        //     return $this->rFail(is_string($push) ? $push : '投递MQ失败');
        // }

        return $this->r(200, 'success', [
            'trade_no' => $tradeNo,
            'status' => $status,
            // 'sync' => $sync,
            // 'sync_result' => $syncResult,
            // 'queued' => $enqueue === 1 ? 1 : 0,
            'main_count' => count($main),
        ]);
    }
     
    //设备设置订单http_out_status状态，供Http兜底方案使用
    public function setHttpOutStatus()
    {
        $order = $this->getSaleOrdersFind(['trade_no' => $this->data['trade_no']]);
        if (!$order) return $this->r(300, $this->lang("VSaleOrders.order_not_data"));
        $this->updateSaleOrders(['http_out_status' => intval($this->data['http_out_status'])],['trade_no' => $this->data['trade_no']]);
        if($this->data['http_out_status'] == 3){
            $triggerResult = $this->triggerOutGoodsByHttp();
            $triggerData = obj2arr($triggerResult);
            if (is_array($triggerData) && isset($triggerData['state']) && intval($triggerData['state']) !== 200) {
                actionLog($triggerData, 'setHttpOutStatus触发闭环失败');
                return $triggerResult;
            }
        }
        return $this->r(200, 'success');
    }


    /**
     * 获取订单支付状态
     * 支持 trade_no / order_id 二选一查询
     * @return array
     */
    public function getOrderPayStatus()
    {
        $where = [];
        if (!empty($this->data['trade_no'])) {
            $where['trade_no'] = $this->data['trade_no'];
        } elseif (!empty($this->data['order_id'])) {
            $where['order_id'] = intval($this->data['order_id']);
        } else {
            return $this->r(100, 'trade_no 或 order_id 不能为空');
        }

        $field = 'order_id,trade_no,mch_no,pay_status,pay_type,pay_method,out_status,refund_status,total_price,refund_amount,create_time,pay_time,out_time,http_out_status';
        $order = $this->getSaleOrdersFind($where, $field);
        if (!$order) {
            return $this->r(300, $this->lang("VSaleOrders.order_not_data"));
        }

        $order = is_object($order) ? (method_exists($order, 'toArray') ? $order->toArray() : (array)$order) : $order;
        $order['now_time'] = time();
        return $this->r(200, 'success', $order);
    }

    /**
     * 获取设备侧销售订单筛选项
     * @return array
     */
    public function getSaleOrderQueryConditionOptions()
    {
        $data = [
            'pay_type_list' => $this->getPayTypeOptions(),
            'pay_channel_list' => [],
        ];

        foreach ($this->getPayChannelNameMap() as $value => $label) {
            $data['pay_channel_list'][] = [
                'value' => intval($value),
                'label' => $label,
            ];
        }

        return $this->r(200, 'success', $data);
    }


    /**
     * 取卡  卡添加积分
     * @return array|\think\response\Json
     * @throws \Exception
     */

    public function cardAddPoints()
    {
        try {
            //带订单号，先直接操作卡，此时卡可能是出的卡，也可能是已出的卡
            if ($this->data['trade_no']) {
                //先校验订单积分是否已经被划走
                $check_data = $this->getCardPointsChangeLogs(['trade_no' => $this->data['trade_no']]);
                if ($check_data) return $this->r(200, 'failed', '当前订单积分已划拨至卡或会员账户，请勿重复操作');
                $order = $this->getSaleOrdersFind(['trade_no' => $this->data['trade_no']], 'total_points');
                if (!$order) return $this->r(200, 'failed', '找不到订单！');
                $order = $order->toArray();
                //如果携带卡信息，判断有没有登录，如果没有登录，积分直接写入卡
                if ($this->data['card_no']) {
                    $card_res = $this->changePoints($this->data['card_no'], $order['total_points'], 1, $this->data['trade_no'], "购买商品增加积分");
                    $bind_card = $this->getCardFind(['card_no' => $this->data['card_no']], 'points,bind_id')->toArray();
                    $card_res['current_integral'] = $bind_card['points'];
                    if ($this->data['bind_id']) {
                        //如果感应卡bind_id有值切不等于传入的bind_id,报错，否则卡绑定bind_id
                        if (!empty($bind_card['bind_id']) && $bind_card['bind_id'] != $this->data['bind_id']) {
                            return $this->r(200, 'failed', '感应卡不在您的会员账户名下！积分已同步至您的会员账户名下。');
                        }
                        //卡是会员的卡，卡内积分同步至微程
                        $card_res = $this->changePoints($this->data['card_no'], $bind_card['points'], 2, $this->data['trade_no'], "会员绑定积分卡", $this->data['bind_id']);
                        //订单积分进卡里了，此时需要把卡内总积分同步到微程
                        $res = $this->wcUserSyncPoints($this->data['token'], $bind_card['points'], 1);
                        if ($res['status'] != 200) {
                            if (strpos($res['response'], "message") !== false) {
                                $response = json_decode($res['response'], true);
                                return $this->r(200, 'failed', $res['response']['message']);
                            } else {
                                return $this->r(200, 'failed', $res['response']);
                            }
                        }
                        $response = json_decode($res['response'], true);
                        $user_points = $response['data']['current_integral'];
                        $this->updateCard(['bind_id' => $this->data['bind_id'], 'bind_id_points' => $user_points], ['card_no' => $this->data['card_no']]);
                        $card_res['current_integral'] = $user_points;
                    }
                } else {
                    //无卡时，判断有没有会员登录，如果有登录，订单积分直接同步到微程会员，如果没登录，积分不做操作
                    if (!empty($this->data['bind_id'])) {
                        $res = $this->wcUserSyncPoints($this->data['token'], $order['total_points'], 1);
                        if ($res['status'] != 200) {
                            if (strpos($res['response'], "message") !== false) {
                                $response = json_decode($res['response'], true);
                                return $this->r(200, 'failed', $res['response']['message']);
                            } else {
                                return $this->r(200, 'failed', $res['response']);
                            }
                        }
                        $response = json_decode($res['response'], true);
                        $user_points = $response['data']['current_integral'];
                        $card_res = $this->changePoints('', $order['total_points'], 1, $this->data['trade_no'], "购买商品增加积分", $this->data['bind_id']);
                        $card_res['current_integral'] = $user_points;
                    }
                }
            } else {
                //机台登录会员后，无订单刷卡场景，直接把卡积分同步到微程会员
                $card = $this->getCardFind(['card_no' => $this->data['card_no']]);
                if (!$card) {
                    $this->addCard(['card_no' => $this->data['card_no'],'password' => md5($this->card_default_pwd.config('app.salt') . $this->data['card_no']),'status'=>1,'activation_time'=>time()]);
                    $card = $this->getCardFind(['card_no' => $this->data['card_no']])->toArray();
                }
                if (!empty($card['bind_id']) && ($card['bind_id'] != $this->data['bind_id']))  return $this->r(200, 'failed', '感应卡已绑定其他会员！！！');
                $card_res = $this->changePoints($this->data['card_no'], $card['points'], 2, '', "会员绑定积分卡", $this->data['bind_id']);
                $this->updateCard(['bind_id' => $this->data['bind_id']], ['card_no' => $this->data['card_no']]);
                $res = $this->wcUserSyncPoints($this->data['token'], $card['points'], 1);
                if ($res['status'] != 200) {
                    if (strpos($res['response'], "message") !== false) {
                        $response = json_decode($res['response'], true);
                        return $this->r(200, 'failed', $res['response']['message']);
                    } else {
                        return $this->r(200, 'failed', $res['response']);
                    }
                }
                $response = json_decode($res['response'], true);
                $user_points = $response['data']['current_integral'];
                $card_res['current_integral'] = $user_points;
            }
            return $this->r(200, 'success', $card_res ?? []);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            return $this->rFail($e->getMessage());
        }
    }

    /**
     * 检查本次余额支付是否需要支付密码
     * 规则：未登录，或登录手机号与卡绑定手机号不一致时，需要输入支付密码
     * @return array|\think\response\Json
     */
    public function checkBalancePayPassword()
    {
        try {
            $cardNo = trim($this->data['card_no'] ?? '');
            $bindId = trim($this->data['bind_id'] ?? '');
            if (!$cardNo) {
                return $this->r(100, 'failed', 'card_no不能为空');
            }

            $card = $this->getCardFind(['card_no' => $cardNo], 'card_no,bind_id,password');
            if (!$card) {
                return $this->r(100, 'failed', '找不到对应的积分卡');
            }

            $needPayPassword = empty($bindId) || empty($card['bind_id']) || ((string)$bindId !== (string)$card['bind_id']);
            $data = [
                'card_no' => $cardNo,
                'bind_id' => $bindId,
                'card_bind_id' => $card['bind_id'] ?? '',
                'need_pay_password' => $needPayPassword ? 1 : 0,
                'has_pay_password' => empty($card['password']) ? 0 : 1,
            ];
            return $this->r(200, 'success', $data);
        } catch (\Exception $e) {
            return $this->rFail($e->getMessage());
        }
    }


    /**
     * 获取积分变化类型
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function getCardChangeLogs()
    {
        $card_points_lists = [];
        $new_data = [];
        $total_card_points = 0;
        $total_balance = '0.00';
        $card_info = [];
        $bind_id = '';
        $card_one = [];
        try {
            if (isset($this->data['bind_id']) && !empty($this->data['bind_id'])) {
                //先判断当前登录账号登录信息是否绑定了当前传入的卡号，如果为绑定，提示用户绑卡
                if (isset($this->data['card_no']) && !empty($this->data['card_no'])) {
                    $card_info = $this->getCardFind(['card_no' => $this->data['card_no']]);
                    if (!$card_info) {
                        $this->addCard(['card_no' => $this->data['card_no'],'password' => md5($this->card_default_pwd.config('app.salt') . $this->data['card_no']),'status'=>1,'activation_time'=>time()]);
                        $card_info = $this->getCardFind(['card_no' => $this->data['card_no']])->toArray();
                    }
                    if (!$card_info['bind_id'])
                        return $this->r(200, 'failed', ['error_code' => 10002, 'message' => '应卡不在您的会员账户名下！是否绑定'], true);

                    if (!empty($card_info['bind_id']) && $card_info['bind_id'] != $this->data['bind_id'])
                        return $this->r(200, 'failed', ['error_code' => 10003, 'message' => '感应卡已绑定其他会员！！！'], true);
                }
                $bind_id = $this->data['bind_id'];
                $card_no_list = $this->getCardColumn(['bind_id' => $bind_id], 'card_no');
                $log_list = $this->getCardPointsChangeLogsList([['card_no', 'in', $card_no_list]], 0, "*", 'id desc', '')->toArray();
                $keys = array_column($log_list, 'card_no');
                foreach ($log_list as $v) {
                    foreach ($keys as $key) {
                        if ($v['card_no'] === $key) {
                            $new_data[$key][] = $v;
                            break;
                        }
                    }
                }
                $card_points_lists = $this->getCardColumn([['card_no', 'in', $card_no_list]], 'card_no,points, bind_id_points');
                $bind_id_column = array_column($card_points_lists, 'bind_id_points');
                $bind_id_points = max($bind_id_column) ?? 0;
                foreach ($card_points_lists as $v) {
                    $total_card_points += $v['points'];
                }
                $card_info = $card_points_lists;
            } elseif (isset($this->data['card_no']) && !empty($this->data['card_no'])) {
                $card = $this->getCardFind(['card_no' => $this->data['card_no']]);
                if (!$card) {
                    $this->addCard(['card_no' => $this->data['card_no'],'password' => md5($this->card_default_pwd.config('app.salt') . $this->data['card_no']),'status'=>1,'activation_time'=>time()]);
                    $card = $this->getCardFind(['card_no' => $this->data['card_no']])->toArray();
                }
                //查询此卡关联的会员id
                $bind_id = $card['bind_id'] ?? '';

                if ($card['bind_id']) {
                    $card_no_list = $this->getCardColumn(['bind_id' => $card['bind_id']], 'card_no');
                    $log_list = $this->getCardPointsChangeLogsList([['card_no', 'in', $card_no_list]], 0, "*", 'id desc', '')->toArray();
                    $keys = array_column($log_list, 'card_no');
                    foreach ($log_list as $v) {
                        foreach ($keys as $key) {
                            if ($v['card_no'] === $key) {
                                $new_data[$key][] = $v;
                                break;
                            }
                        }
                    }
                    $card_points_lists = $this->getCardColumn([['card_no', 'in', $card_no_list]], 'card_no,points, bind_id_points');
                    $bind_id_column = array_column($card_points_lists, 'bind_id_points');
                    $bind_id_points = max($bind_id_column) ?? 0;
                    foreach ($card_points_lists as $v) {
                        $total_card_points += $v['points'];
                    }
                    $card_info = $card_points_lists;
                } else {
                    $card_info = [$card];
                    $new_data[$card['card_no']] = $this->getCardPointsChangeLogsList(['card_no' => $this->data['card_no']]);
                    $total_card_points = $card['points'];
                    $bind_id_points = $card['bind_id_points'];
                }
            }

            $res['data'] = $new_data;
            $res['card_info'] = $card_info;
            $res['total_card_points'] = $total_card_points;
            $res['bind_id'] = $bind_id;
            $res['bind_id_points'] = $bind_id_points;
            $res['total_points'] = $res['total_card_points'] + $res['bind_id_points'];
            $currentCardNo = trim((string)($this->data['card_no'] ?? ''));
            if ($currentCardNo !== '') {
                // try {
                //     $summaryMethod = new \ReflectionMethod($this, 'getCardBalanceSummary');
                //     actionLog([
                //         'class' => $summaryMethod->getDeclaringClass()->getName(),
                //         'file' => $summaryMethod->getFileName(),
                //         'start_line' => $summaryMethod->getStartLine(),
                //         'end_line' => $summaryMethod->getEndLine(),
                //     ], '卡余额汇总方法定位');

                //     $bucketMethod = new \ReflectionMethod($this, 'getCardBalanceBucketSummaryRow');
                //     actionLog([
                //         'class' => $bucketMethod->getDeclaringClass()->getName(),
                //         'file' => $bucketMethod->getFileName(),
                //         'start_line' => $bucketMethod->getStartLine(),
                //         'end_line' => $bucketMethod->getEndLine(),
                //     ], '卡余额分笔方法定位');
                // } catch (\Exception $e) {
                //     actionLog($e->getMessage(), '卡余额方法定位异常');
                // }

                // $bucketCount = \app\AppFactory\Kernel\Model\Card\CardBalanceBucketsModel::where('card_no', $currentCardNo)->count();
                // actionLog($bucketCount, '当前卡分笔记录数量');
                $summary = $this->getCardBalance($currentCardNo);
                actionLog($summary, '查询卡余额返回内容');
                $total_balance = (string)($summary['available_balance'] ?? '0.00');
            }
            actionLog($currentCardNo, '当前查询的卡号');
            $res['total_balance'] = $total_balance;
            //用户是否需要输入密码
            $res['need_pay_password'] = 1;
            $check_password = md5(config('app.salt') . $this->data['card_no']);
            if(!empty($card['password']) && $card['password'] == $check_password){
                $res['need_pay_password'] = 0;
            }
            return $this->r(200, 'success', $res);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            return $this->rFail($e->getMessage());
        }
    }

    /**
     * 设备商获取短信验证码
     * @return array|\think\response\Json
     * @throws \Exception 
     */
    public function getWcSmSCode()
    {
        $res = $this->getSmsCode($this->data['phone'], $this->data['machine_id']);
        $response = json_decode($res['response'], true);
        if (isset($response['data'])) {
            return $this->r(200, "success", $response['data']);
        }
        if (isset($response['message'])) {
            return $this->r(200, "success", $response['message']);
        }
    }

    /**
     * 微程会员登录
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function getWcLoginUser()
    {
        $res = $this->wcLoginUser($this->data['phone'], $this->data['machine_id'], $this->data['code']);
        // $res['response'] = '{"success":true,"message":"登录成功","token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJ7XCJ1c2VySWRcIjo3OTYyMjYwfSIsImV4cCI6MTc2ODgyOTIyOCwiaWF0IjoxNzY4ODI4NjI4fQ.LgquQkybzpcmJ1dgjAA3HsL7RA0iwgnV2slr-3C3pOE"}';
        actionLog($res, '登录微程返回内容');
        if ($res['status'] != 200) {
            if (strpos($res['response'], "message") !== false) {
                $res_response = json_decode($res['response'], true);
                return $this->r(200, 'failed', ['success' => false, 'message' => $res_response['message']]);
            } else {
                return $this->r(200, 'failed', ['success' => false, 'message' => $res['response']]);
            }
        }
        $response = json_decode($res['response'], true);
        $token = $response['token'];
        //调用微程接口，获取用户地址信息，数据入库
        $address_lists = $this->getWcUserInfo($this->data['phone'], $token);

        $card_lists = $this->getCardList(['bind_id' => $this->data['phone']]);
        if (!$card_lists) {
            $response['card_lists'] = [];
            $response['address_lists'] = $address_lists;
            return $this->r(200, "success", $response);
        }
        $card_lists = $card_lists->toArray();
        //用户在机台登录时，就同步积分到微程
        foreach ($card_lists as $card) {
            if (!$card['points']) continue;
            $card = $this->getCardFind(['card_no' => $card['card_no']])->toArray();
            if ($card['points'] > 0) {
                $card_res = $this->wcUserSyncPoints($token, $card['points'], 1);
            } else {
                $card_points_abs = abs($card['points']);
                $card_res = $this->wcUserSyncPoints($token, $card_points_abs, 0);
            }

            $card_change_res = $this->changePoints($card['card_no'], $card['points'], 2, '', "卡内积分同步至会员积分账户", $this->data['phone']);
            $res_response = json_decode($card_res['response'], true);
            actionLog($res_response, '同步卡积分微程返回内容');
            if (isset($res_response) && isset($res_response['data']) && isset($res_response['data']['current_integral'])) {
                $this->updateCard(['bind_id_points' => $res_response['data']['current_integral']], ['card_no' => $card['card_no']]);
                actionLog($card['card_no'] . '--' . $res_response['data']['current_integral'], '同步卡积分微程返回内容');
            }
        }
        $card_lists = $this->getCardList(['bind_id' => $this->data['phone']])->toArray();
        $response['card_lists'] = $card_lists;
        $response['address_lists'] = $address_lists;
        return $this->r(200, "success", $response);
    }

    /**
     * 获取当前设备最近两分钟内最后一条微程登录信息。
     */
    public function getWcLatestLoginInfo()
    {
        $where = [
            ['machine_id', '=', $this->machine['machine_id']],
            ['create_time', '>=', time() - 120],
        ];
        $lastLoginInfoId = intval($this->data['last_login_info_id'] ?? 0);
        if ($lastLoginInfoId > 0) {
            $where[] = ['wuli_id', '>', $lastLoginInfoId];
        }
        $loginInfo = $this->getWcUserLoginInfoFind(
            $where,
            'wuli_id,phone,login_data,mq_status,create_time',
            'create_time desc,wuli_id desc'
        );

        if (!$loginInfo) {
            return $this->r(200, '暂无两分钟内的新登录信息', []);
        }
        $loginInfo = obj2arr($loginInfo);
        $data = json_decode($loginInfo['login_data'] ?? '', true);
        if (!is_array($data)) {
            return $this->r(300, '登录信息格式错误');
        }
        $data['login_info_id'] = intval($loginInfo['wuli_id']);
        $data['login_time'] = intval($loginInfo['create_time']);
        $data['mq_status'] = intval($loginInfo['mq_status']);
        return $this->r(200, 'success', $data);
    }

    /**
     * 微程会员同步积分
     * 微程会员在售卖机登录后，
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function setWcUserAddPoints()
    {
        $order = $this->getSaleOrdersFind(['trade_no' => $this->data['trade_no']], 'total_points');
        if (!$order) return $this->r(100, "查无此订单");
        $order = $order->toArray();
        $res = $this->wcUserSyncPoints($this->data['token'], $order['total_points'], 1);
        if ($res['status'] !== 200) return $this->r(100, 'failed', $res['response']);
        $response = json_decode($res['response'], true);
        return $this->r(200, 'success', $response);
    }
    /**
     * 获取微程积分二维码
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function getWcPointsQrcode()
    {
        $order = $this->getSaleOrdersFind(['trade_no' => $this->data['trade_no']], 'total_points');
        if (!$order) return $this->r(100, 'failed', "查无此订单");
        $order = $order->toArray();
        $res = $this->wcPointsQrCode($order['total_points']);
        if ($res['status'] !== 200) return $this->r(100, 'failed', $res['response']);
        $response = json_decode($res['response'], true);
        return $this->r(200, 'success', $response);
    }


    /**
     * 卡积分绑定到微程会员账号，同时将卡积分清0
     * @return array|\think\response\Jsonz
     * @throws \Exception
     */

    public function cardBindWcuser()
    {
        try {
            // $order = $this->getSaleOrdersFind(['trade_no' => $this->data['trade_no']], 'total_points')->toArray();
            // $res = $this->changePoints($this->data['card_no'], $order['total_points'], 1, $this->data['trade_no'], "购买商品增加积分");
            // return $this->r(200, $res);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            return $this->rFail($e->getMessage());
        }
    }

    //创建卡购买订单
    public function addCardSaleOrdersAndDetails()
    {
        $card_retail_price = $this->data['price'] ?: $this->card_retail_price;
        if ($this->data['pay_method'] == "41") $this->data['pay_method'] = 1;
        $trade_no = date("YmdHis") . $this->machine['m_id'] . $this->get_rand_string(6, "num");


        $m = $this->getMachineFind(['m_id' => $this->machine['m_id']], 'factory,inventory_location');

        $order = [
            "trade_no" => $trade_no,
            "m_id" => $this->machine['m_id'],
            "machine_name" => $this->machine['machine_name'],
            "machine_id" => $this->machine['machine_id'],
            //            "manager_id" => $this->machine['manager_id'],
            "ao_id" => $this->machine['ao_id'],
            "pay_type" => $this->data['pay_type'],
            "pay_method" => $this->data['pay_method'],
            // "mobile" => $this->data['mobile'] ?? "",
            "create_date" => strtotime(date("Y-m-d")),
            "factory" => $m['factory'] ? $m['factory'] : '',
            "inventory_location" => $m['inventory_location'] ? $m['inventory_location'] : ''

        ];
        $updateOrder = [];
        $this->startTrans();
        try {
            $order_id = $this->addSaleOrders($order);
            if ($order_id) {
                $updateOrder['order_id'] = $order_id;
                $updateOrder['cost_price'] = 0;
                $updateOrder['market_price'] = 0;
                $updateOrder['retail_price'] = $card_retail_price;
                $updateOrder['quantity'] = 1;
                $updateOrder['total_price'] = $card_retail_price;
                $updateOrder['total_quantity'] = 1;

                $details = [
                    "order_id" => $order_id,
                    "mc_id" => 0,
                    "shelf_way" => 1,
                    "channel_position" => 1,
                    "channel_code" => 'Z20',
                    "mg_id" => 999999,
                    "g_id" => 999999,
                    "g_name" => '会员积分卡',
                    "pic" => '',
                    "sku" => 1000000000001,
                    "gc_id" => 999999,
                    "gc_name" => '会员积分卡',
                    "cost_price" => 0,
                    "market_price" => 0,
                    "retail_price" => $card_retail_price,
                    "total_sod_price" => $card_retail_price,
                    "quantity" => 1,
                    "bar_code" => 1000000000001,
                ];
                $sod_id = $this->addSaleOrdersDetails($details);
                $updateOrder['retail_price'] = $updateOrder['total_price'];
                $flag[] = $this->updateSaleOrders($updateOrder);
                $this->order = $this->getSaleOrdersFind(['order_id' => $order_id]);
                $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $order_id], 0);
                actionLog($this->getLS(), '修改订单SQL');
                $result = $this->checkFlag($flag);
                actionLog($result, '事务结果');
                $this->commitTrans();
                return $this->r(200, $this->lang("VSubCar.make_order_success"), ['order' => $this->order]);
            } else {
                $this->rollbackTrans();
                return $this->r(300, $this->lang("VSubCar.make_order_fail"));
            }
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getWcGoodsLocalLists()
    {
        $pageNum = $this->data['pageNum'] ?? 15;
        $where['status'] = 1;
        $field = "g_id,no,g_name,gc_id,gc_name,g_type,g_type_name,pic,retail_price,desc,sell_channel,status";
        $wcGoodsLocalLists = $this->getWcGoodsLocalList($where, $pageNum, $field, 'g_id desc');
        if ($wcGoodsLocalLists) $wcGoodsLocalLists = $wcGoodsLocalLists->toArray();
        return $this->r(200, "SUCCESS", $wcGoodsLocalLists);
    }

    public function getWcMCLists()
    {
        $pageNum = $this->data['pageNum'] ?? 15;
        if (isset($this->data['m_id'])) $where['m_id'] = $this->data['m_id'];
        $where['machine_id'] = $this->data['machine_id'];
        $where['is_hidden'] = 2;
        $wcMachineChannelLists = $this->getWcMachineChannelList($where, $pageNum, "*", 'sort asc');
        if ($wcMachineChannelLists) $wcMachineChannelLists = $wcMachineChannelLists->toArray();
        $wcMachineChannelData = $pageNum ? $wcMachineChannelLists['data'] : $wcMachineChannelLists;
        foreach ($wcMachineChannelData as &$v) {
            $wc_goods = $this->getWcGoodsFind(['no' => $v['out_no']]);
            $v['desc'] = $wc_goods['description'] ?? '';
            if ($v['gc_id'] == 11) {
                $daysInfo = $this->getWcGoodsColumn(['no' => $v['out_no']], 'daysInfo');
                if ($daysInfo) $v['daysInfo'] = $daysInfo[0] ?? [];
            }
            $v['goods_lists'] = $this->getWcGoodsLocalList(['out_no' => $v['out_no']])->toArray();
            foreach($v['goods_lists'] as &$item){
                $item['desc'] .= $wc_goods['description'] ?? '';
            }
        }
        unset($v, $item);
        if ($pageNum) $wcMachineChannelLists['data'] = $wcMachineChannelData;
        return $this->r(200, "SUCCESS", $wcMachineChannelLists);
    }

    public function getWcUserInfo($bind_id, $token)
    {
        $res = $this->syncWcUserInfo($token);
        if ($res['status'] != 200) return $this->r(100, 'failed', $res['response']);

        $response = json_decode($res['response'], true);
        $address_lists = $response['data']['addressList'] ?? [];

        if (!empty($address_lists)) {
            foreach ($address_lists as $v) {
                $setData = [
                    'bind_id' => $bind_id,
                    'address' => trim($v['address']),
                    'link_name' => $v['link_name'],
                    'phone' => $v['phone'],
                ];
                $check = $this->getWcUserAddressesFind($setData);
                if (!$check) {
                    $this->addWcUserAddresses($setData);
                } else {
                    $this->updateWcUserAddresses($setData, ['id' => $check['id']]);
                }
            }
        }
        return $address_lists;
    }


    public function getWcUserAddress()
    {
        $bind_id = $this->data['bind_id'] ?? '';
        $card_no = $this->data['card_no'] ?? '';
        $address_lists = [];
        if (empty($bind_id) && empty($card_no)) return $this->r(100, 'failed', 'bind_id和card_no不能同时为空');
        if ($card_no) {
            $card_info = $this->getCardFind(['card_no' => $card_no], 'bind_id');
            if (!$card_info) return $this->r(100, 'failed', '找不到对应的卡信息');
            if ($bind_id && $bind_id != $card_info['bind_id']) return $this->r(100, 'failed', '警告：该卡不在当前会员账户名下');
            $address_lists = $this->getWcUserAddressesList(['bind_id' => $card_info['bind_id']]);
        }
        if ($bind_id) {
            $address_lists = $this->getWcUserAddressesList(['bind_id' => $bind_id]);
        }
        return $this->r(200, 'success', $address_lists);
    }

    public function getLoginWcQrCode()
    {
        $machine_id = $this->data['machine_id'];
        $res = $this->wcLoginQrCode($machine_id);
        if ($res['status'] != 200) return $this->r(100, 'failed', $res['response']);

        $response = json_decode($res['response'], true);
        return $this->r(200, 'success', $response['data']);
    }

    public function getMachineRentOrgLists()
    {
        $where['machine_id'] = $this->data['machine_id'];
        $rent_machine_orgs = $this->getAuthOrgMCColumn($where, 'ao_id');
        return $this->r(200, "SUCCESS", $rent_machine_orgs);
    }

    public function getRentOrgGoodsLists()
    {
        $where['ao_id'] = $this->data['ao_id'];
        $where['status'] = 1;
        $pageNum = $this->data['pageNum'] ?? 15;
        $orgGoodsLists = $this->getGoodsList($where, $pageNum);

        return $this->r(200, "SUCCESS", $orgGoodsLists);
    }

    public function searchWCGoods(){
        $where['is_pub'] = 1;
        if(isset($this->data['name'])) $where[] = ['name', 'like', '%'.$this->data['name'].'%'];
        $pageNum = $this->data['pageNum'] ?? 15;
        $orgGoodsLists = $this->getWcGoodsList($where, $pageNum);

        return $this->r(200, "SUCCESS", $orgGoodsLists);
    }

    public function test()
    {
        $trade_no = $this->data['trade_no'] ?? '';
        $this->refundTradeNo = $this->data['refund_trade_no'];
        $order_id = $this->data['order_id'] ?? 0;
        $sod_id = $this->data['sod_id'] ?? 0;
        $this->order = $this->getSaleOrdersFind(['trade_no' => $trade_no])->toArray();;
        $this->refund = $this->getSaleOrdersRefundFind(['trade_no' => $trade_no]);
        $this->refundSuccess();;
        die();
        $this->paymentSuccessful();
        $this->addCardChangeLog();
        // $this->outGoods();
        die();
        // $order = $this->outGoods();
        $detail = $this->getSaleOrdersDetailsFind(['sod_id' => $sod_id]);
        return $this->orderRefundSync2Wc($this->order, $detail);
    }

    public function getCardBalance($card_no)
    {
        return $this->getCardBalanceSummary($card_no);
    }

    /**
     * 获取维护项目（树形）
     * @return array|\think\response\Json
     */
    public function getMaintenanceItems()
    {
        try {
            $items = Db::name('maintenance_items')
                ->where(['is_active' => 1])
                ->field('id,parent_id,item_name,item_level,cycle_days,description,sort_order,is_active,updated_at')
                ->order('sort_order asc,id asc')
                ->select()
                ->toArray();

            return $this->r(200, 'SUCCESS', $this->buildMaintenanceItemTree($items));
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 提交维护记录
     * 参数：check_list（每项包含 item_id, check_status=1|2, notes可选）, maintainer_id
     * @return array|\think\response\Json
     */
    public function submitMaintenanceRecord()
    {
        try {
            $checkList = $this->data['check_list'] ?? [];
            if (!is_array($checkList)) {
                $decoded = json_decode(strval($checkList), true);
                $checkList = is_array($decoded) ? $decoded : [];
            }
            if (!$checkList) {
                return $this->rValidate('check_list不能为空');
            }

            $submittedMap = [];
            foreach ($checkList as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $itemId = intval($row['item_id'] ?? ($row['id'] ?? 0));
                if ($itemId <= 0) {
                    continue;
                }

                $submittedMap[$itemId] = [
                    'check_status' => intval($row['check_status'] ?? 0),
                    'notes' => trim(strval($row['notes'] ?? '')),
                ];
            }

            if (!$submittedMap) {
                return $this->rValidate('check_list不能为空');
            }

            $itemIds = array_values(array_map('intval', array_keys($submittedMap)));
            $itemRows = Db::name('maintenance_items')
                ->where([['id', 'in', $itemIds]])
                ->field('id,parent_id,item_level')
                ->select()
                ->toArray();

            $itemMap = [];
            foreach ($itemRows as $itemRow) {
                $itemMap[intval($itemRow['id'])] = [
                    'parent_id' => intval($itemRow['parent_id'] ?? 0),
                    'item_level' => intval($itemRow['item_level'] ?? 0),
                ];
            }

            $missing = array_values(array_diff($itemIds, array_keys($itemMap)));
            if ($missing) {
                return $this->rFail('维护项目不存在:' . implode(',', $missing));
            }

            $submittedChildIds = [];
            $parentIds = [];
            foreach ($itemIds as $itemId) {
                $item = $itemMap[$itemId] ?? [];
                if (intval($item['item_level'] ?? 0) === 1) {
                    continue;
                }
                $parentId = intval($item['parent_id'] ?? 0);
                if ($parentId <= 0) {
                    continue;
                }
                $submittedChildIds[] = intval($itemId);
                $parentIds[] = $parentId;
            }

            $submittedChildIds = array_values(array_unique($submittedChildIds));
            $parentIds = array_values(array_unique($parentIds));

            $invalidStatusIds = [];
            foreach ($submittedChildIds as $itemId) {
                $submittedRow = $submittedMap[$itemId] ?? [];
                $checkStatus = intval($submittedRow['check_status'] ?? 0);
                if (!in_array($checkStatus, [1, 2], true)) {
                    $invalidStatusIds[] = intval($itemId);
                }
            }
            if ($invalidStatusIds) {
                return $this->rFail('check_status必须为1或2, item_id:' . implode(',', array_values(array_unique($invalidStatusIds))));
            }

            $recordsCode = date('YmdHi');
            $maintainerId = trim(strval($this->data['maintainer_id'] ?? ''));
            if ($maintainerId === '') {
                return $this->rValidate('maintainer_id不能为空');
            }

            $defaultNotes = trim(strval($this->data['notes'] ?? ''));
            $maintenanceTime = date('Y-m-d H:i:s');

            if (!$parentIds) {
                return $this->r(200, 'SUCCESS', [
                    'records_code' => $recordsCode,
                    'machine_id' => $this->machine['machine_id'],
                    'count' => 0,
                ]);
            }

            $recordItemIds = Db::name('maintenance_items')
                ->where([['parent_id', 'in', $parentIds]])
                ->where(['is_active' => 1])
                ->where('item_level', '<>', 1)
                ->order('sort_order asc,id asc')
                ->column('id');
            $recordItemIds = array_values(array_unique(array_map('intval', $recordItemIds)));

            $insertAll = [];
            foreach ($recordItemIds as $itemId) {
                $rowNotes = strval($submittedMap[$itemId]['notes'] ?? '');
                $insertAll[] = [
                    'records_code' => $recordsCode,
                    'item_id' => $itemId,
                    'machine_id' => $this->machine['machine_id'],
                    'maintainer_id' => $maintainerId,
                    'check_status' => isset($submittedMap[$itemId])
                        ? intval($submittedMap[$itemId]['check_status'] ?? 0)
                        : 2,
                    'maintenance_time' => $maintenanceTime,
                    'notes' => $rowNotes !== '' ? $rowNotes : $defaultNotes,
                ];
            }

            if (!$insertAll) {
                return $this->r(200, 'SUCCESS', [
                    'records_code' => $recordsCode,
                    'machine_id' => $this->machine['machine_id'],
                    'count' => 0,
                ]);
            }

            Db::startTrans();
            $result = Db::name('maintenance_records')->insertAll($insertAll);
            if (!$result) {
                Db::rollback();
                return $this->rFail('维护记录提交失败');
            }
            Db::commit();

            return $this->r(200, 'SUCCESS', [
                'records_code' => $recordsCode,
                'machine_id' => $this->machine['machine_id'],
                'count' => count($insertAll),
            ]);
        } catch (\Throwable $e) {
            Db::rollback();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 从文件内容生成 maintenance_records 的 INSERT SQL
     * 接收参数：file_content (string)，可选参数：per_row (bool) 默认为 false（生成一个多行 VALUES 的 INSERT）
     * 文档列对应：| maintenance_time | 无意义 | maintenance_item.id 或 item_name | maintainer_id |
     * 如果第三列不是数字，会尝试按 item_name 在 maintenance_items 表查找 id，找不到会返回错误信息
     * @return array|\think\response\Json
     */
    public function importMaintenanceRecordsFromFile()
    {
        $content = trim(strval($this->data['file_content'] ?? ''));
        $perRow = !empty($this->data['per_row']);
        if ($content === '') {
            return $this->rValidate('file_content不能为空');
        }

        // 移除可能的 Markdown 代码块标记
        $content = preg_replace('/^```[\s\S]*|[\s\S]*```$/', '', $content);
        $lines = preg_split('/\r?\n/', $content);
        $insertAll = [];
        $errors = [];
        $missingItems = [];
        $machineId = $this->machine['machine_id'] ?? '';

        // 仅处理形如: | 2026-05-15 16:14:38 | 完成 | 齿轮 / 齿条 | 123 | 的生效行，其他行跳过
        // 列位定义：| maintenance_time | 无意义状态文本 | maintenance_item.id或item_name | maintainer_id |
        $patternMaintenance = '/^\|\s*(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s*\|\s*([^|]*)\s*\|\s*([^|]+?)\s*\|\s*([^|]+?)\s*\|?$/';
        foreach ($lines as $ln) {
            $ln = trim($ln);
            if ($ln === '') continue;
            if (!preg_match($patternMaintenance, $ln, $match)) {
                // 非生效格式行跳过
                continue;
            }

            $maintenanceTimeRaw = trim($match[1]);
            // $match[2] 为状态文本（例如：完成），按需求无业务意义，忽略
            $itemCol = trim($match[3]);
            $maintainerId = trim($match[4]);

            $ts = strtotime($maintenanceTimeRaw);
            if ($ts === false) {
                $errors[] = "时间格式无效: {$maintenanceTimeRaw}";
                continue;
            }
            $maintenanceTime = date('Y-m-d H:i:s', $ts);
            $recordsCode = date('YmdHi', $ts);

            $itemId = null;
            if (is_numeric($itemCol)) {
                $itemId = intval($itemCol);
            } else {
                $itemName = $itemCol;
                $itemId = Db::name('maintenance_items')->where('item_name', $itemName)->value('id');
                if (!$itemId) {
                    $missingItems[] = $itemName;
                    continue;
                }
            }

            $insertAll[] = [
                'records_code' => $recordsCode,
                'item_id' => intval($itemId),
                'machine_id' => $machineId,
                'maintainer_id' => trim($maintainerId),
                'maintenance_time' => $maintenanceTime,
                'notes' => '',
            ];
        }

        if ($missingItems) {
            $missingItems = array_values(array_unique($missingItems));
            return $this->rFail('未找到对应的维护项目: ' . implode(',', $missingItems));
        }
        if (empty($insertAll)) {
            return $this->rFail('没有可插入的记录', ['errors' => $errors]);
        }

        // 构建 SQL 字符串用于日志记录
        $columns = "`records_code`,`item_id`,`machine_id`,`maintainer_id`,`maintenance_time`,`notes`";
        $valueTuples = [];
        foreach ($insertAll as $row) {
            $v = [
                addslashes($row['records_code']),
                addslashes($row['item_id']),
                addslashes($row['machine_id']),
                addslashes($row['maintainer_id']),
                addslashes($row['maintenance_time']),
                addslashes($row['notes']),
            ];
            $valueTuples[] = "('" . implode("','", $v) . "')";
        }
        $sql = "INSERT INTO `maintenance_records` ({$columns}) VALUES " . implode(' , ', $valueTuples) . ";";

        // 记录将要执行的 SQL（插入前）
        actionLog(['machine_id' => $machineId, 'sql' => $sql, 'count' => count($insertAll)], 'importMaintenanceRecordsFromFile_sql_before');

        // 执行批量插入
        try {
            Db::startTrans();
            $result = Db::name('maintenance_records')->insertAll($insertAll);
            if ($result === false) {
                Db::rollback();
                actionLog(['machine_id' => $machineId, 'sql' => $sql, 'error' => 'insertAll returned false'], 'importMaintenanceRecordsFromFile_error');
                return $this->rFail('维护记录插入失败');
            }
            Db::commit();

            // 记录操作日志（插入后）
            actionLog(['machine_id' => $machineId, 'sql' => $sql, 'count' => $result], 'importMaintenanceRecordsFromFile_sql_after');

            return $this->r(200, 'SUCCESS', [
                'count' => $result,
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            Db::rollback();
            // 异常也写日志，包含 SQL 与异常信息
            actionLog(['machine_id' => $machineId, 'sql' => $sql, 'exception' => $e->getMessage()], 'importMaintenanceRecordsFromFile_exception');
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 获取维护记录（按records_code归类）
     * @return array|\think\response\Json
     */
    public function getMaintenanceRecords()
    {
        try {
            $where = [];
            $where[] = ['mr.machine_id', '=', $this->machine['machine_id']];
            if (!empty($this->data['records_code'])) {
                $where[] = ['mr.records_code', '=', trim($this->data['records_code'])];
            }

            $page = max(1, intval($this->data['page'] ?? 1));
            $pageSize = intval($this->data['pageNum'] ?? ($this->data['pageSize'] ?? 0));
            $total = 0;

            $groupSql = Db::name('maintenance_records')
                ->alias('mr')
                ->where($where)
                ->field('mr.records_code')
                ->group('mr.records_code')
                ->fetchSql(true)
                ->select();
            $totalSql = "SELECT COUNT(1) AS tp_count FROM ({$groupSql}) AS t";
            actionLog([
                'machine_id' => $this->machine['machine_id'] ?? '',
                'where' => $where,
                'totalSql' => $totalSql,
            ], 'getMaintenanceRecords_total_sql');
            $totalRows = Db::query($totalSql);
            $total = intval($totalRows[0]['tp_count'] ?? 0);
            if ($pageSize > 0) {
                
                $codes = Db::name('maintenance_records')->alias('mr')
                    ->where($where)
                    ->field('mr.records_code,max(mr.id) as max_id')
                    ->group('mr.records_code')
                    ->order('max_id desc')
                    ->page($page, $pageSize)
                    ->select()
                    ->column('records_code');

                if (!$codes) {
                    return $this->r(200, 'SUCCESS', [
                        'list' => [],
                        'pagination' => [
                            'page' => $page,
                            'pageSize' => 0,
                            'total' => $total,
                            'totalPage' => 0,
                        ],
                    ]);
                }

                $where[] = ['mr.records_code', 'in', $codes];
            }

            $list = Db::name('maintenance_records')
                ->alias('mr')
                ->leftJoin('maintenance_items mi', 'mi.id = mr.item_id')
                ->leftJoin('auth_manager am', 'am.manager_id = mr.maintainer_id')
                ->where($where)
                ->field("mr.id,mr.records_code,mr.item_id,mr.machine_id,mr.maintainer_id,mr.check_status,mr.maintenance_time,mr.notes,mr.created_at,mi.item_name,mi.description,mi.parent_id,mi.item_level,mi.cycle_days,IFNULL(NULLIF(am.nickname,''), mr.maintainer_id) as nickname")
                ->order('mr.records_code desc,mr.id asc')
                ->select()
                ->toArray();
            $grouped = [];
            foreach ($list as $item) {
                $code = $item['records_code'];
                if (!isset($grouped[$code])) {
                    $grouped[$code] = [
                        'records_code' => $code,
                        'machine_id' => $item['machine_id'],
                        'maintainer_id' => $item['maintainer_id'],
                        'nickname' => $item['nickname'] ?? '',
                        'check_status' => $item['check_status'],
                        'maintenance_time' => $item['maintenance_time'],
                        'next_maintenance_date' => '',
                        'records' => [],
                    ];
                }
                $cycleDays = intval($item['cycle_days'] ?? 0);
                $maintainTime = $item['maintenance_time'] ?: $grouped[$code]['maintenance_time'];
                $nextDate = '';
                if ($cycleDays > 0 && $maintainTime) {
                    $ts = is_numeric($maintainTime) ? intval($maintainTime) : strtotime((string)$maintainTime);
                    if ($ts) {
                        $nextDate = date('Y-m-d', strtotime('+' . $cycleDays . ' days', $ts));
                    }
                }
                $grouped[$code]['records'][] = [
                    'id' => $item['id'],
                    'item_id' => $item['item_id'],
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? '',
                    'parent_id' => $item['parent_id'],
                    'item_level' => $item['item_level'],
                    'cycle_days' => $cycleDays,
                    'maintainer_id' => $item['maintainer_id'],
                    'nickname' => $item['nickname'] ?? '',
                    'check_status' => $item['check_status'],
                    'maintenance_time' => $item['maintenance_time'],
                    'next_maintenance_date' => $nextDate,
                    'notes' => $item['notes'],
                    'created_at' => $item['created_at'],
                ];
                if ($nextDate !== '') {
                    $prev = $grouped[$code]['next_maintenance_date'] ?? '';
                    $grouped[$code]['next_maintenance_date'] = ($prev === '' || $nextDate < $prev) ? $nextDate : $prev;
                }
            }

            $result = array_values($grouped);
            if ($pageSize > 0) {
                $currentCount = count($result);
                $responsePageSize = $currentCount;
                return $this->r(200, 'SUCCESS', [
                    'list' => $result,
                    'pagination' => [
                        'page' => $page,
                        'pageSize' => $responsePageSize,
                        'total' => $total,
                        'totalPage' => $responsePageSize > 0 ? (int)ceil($total / $responsePageSize) : 0,
                    ],
                ]);
            }

            return $this->r(200, 'SUCCESS', $result);
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 维护项目树形结构
     * @param array $items
     * @return array
     */
    protected function buildMaintenanceItemTree($items)
    {
        $nodes = [];
        foreach ($items as $item) {
            $item['children'] = [];
            $nodes[$item['id']] = $item;
        }

        $tree = [];
        foreach ($nodes as $id => $node) {
            $parentId = intval($node['parent_id']);
            if ($parentId > 0 && isset($nodes[$parentId])) {
                $nodes[$parentId]['children'][] = &$nodes[$id];
            } else {
                $tree[] = &$nodes[$id];
            }
        }
        return $tree;
    }

    /**
     * 获取检查清单项目（树形，一级固定：基础状态/商品陈列/核心功能）
     * @return array|\think\response\Json
     */
    public function getCheckListItems()
    {
        try {
            $items = Db::name('check_list_items')
                ->where(['is_active' => 1])
                ->field('id,parent_id,item_name,item_level,description,sort_order,is_active,updated_at')
                ->order('sort_order asc,id asc')
                ->select()
                ->toArray();

            $tree = $this->buildMaintenanceItemTree($items);
            $tree = $this->mergeDefaultCheckListRoots($tree);
            return $this->r(200, 'SUCCESS', $tree);
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 提交检查清单记录
     * 参数：check_list（每项包含 item_id, check_status=1|2, notes可选）
     * @return array|\think\response\Json
     */
    public function submitCheckListRecord()
    {
        try {
            $checkList = $this->data['check_list'] ?? [];
            if (!is_array($checkList)) {
                $decoded = json_decode(strval($checkList), true);
                $checkList = is_array($decoded) ? $decoded : [];
            }
            if (!$checkList) {
                return $this->rValidate('check_list不能为空');
            }

            $enabledItems = Db::name('check_list_items')
                ->where(['is_active' => 1])
                ->field('id,item_name,item_level')
                ->order('sort_order asc,id asc')
                ->select()
                ->toArray();
            if (!$enabledItems) {
                return $this->rFail('暂无启用检查项');
            }

            $enabledMap = [];
            foreach ($enabledItems as $enabledItem) {
                $enabledMap[intval($enabledItem['id'])] = $enabledItem;
            }
            $requiredItemIds = [];
            foreach ($enabledMap as $enabledId => $enabledItem) {
                if (intval($enabledItem['item_level'] ?? 0) !== 1) {
                    $requiredItemIds[] = intval($enabledId);
                }
            }

            $submittedMap = [];
            $invalidStatusItems = [];
            $submittedIds = [];
            foreach ($checkList as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $itemId = intval($row['item_id'] ?? ($row['id'] ?? 0));
                if ($itemId <= 0) {
                    continue;
                }
                $submittedIds[] = $itemId;
                $checkStatus = intval($row['check_status'] ?? 0);
                $rowNotes = trim(strval($row['notes'] ?? ''));
                $submittedMap[$itemId] = [
                    'check_status' => $checkStatus,
                    'notes' => $rowNotes,
                ];

                if (!isset($enabledMap[$itemId])) {
                    continue;
                }
                $itemLevel = intval($enabledMap[$itemId]['item_level'] ?? 0);
                if ($itemLevel !== 1 && !in_array($checkStatus, [1, 2], true)) {
                    $invalidStatusItems[$itemId] = [
                        'item_id' => $itemId,
                        'item_name' => $enabledMap[$itemId]['item_name'] ?? '',
                        'reason' => 'check_status必须为1或2',
                    ];
                }
            }

            if (!$submittedMap) {
                return $this->rValidate('check_list不能为空');
            }

            $submittedIds = array_values(array_unique(array_map('intval', $submittedIds)));
            $existSubmittedIds = Db::name('check_list_items')->where([['id', 'in', $submittedIds]])->column('id');
            $notExists = array_values(array_diff($submittedIds, $existSubmittedIds));
            if ($notExists) {
                return $this->rFail('检查项不存在:' . implode(',', $notExists));
            }
            $disabledIds = array_values(array_diff($submittedIds, array_keys($enabledMap)));
            if ($disabledIds) {
                return $this->rFail('检查项已禁用:' . implode(',', $disabledIds));
            }

            $missingStatusItems = [];
            foreach ($requiredItemIds as $itemId) {
                $enabledItem = $enabledMap[$itemId] ?? [];
                if (!isset($submittedMap[$itemId])) {
                    $missingStatusItems[] = [
                        'item_id' => $itemId,
                        'item_name' => $enabledItem['item_name'] ?? '',
                        'reason' => '未提交check_status',
                    ];
                }
            }
            if ($invalidStatusItems) {
                $missingStatusItems = array_merge($missingStatusItems, array_values($invalidStatusItems));
            }
            if ($missingStatusItems) {
                return $this->r(300, '存在未提交check_status的信息', [
                    'missing_check_status_items' => $missingStatusItems,
                ]);
            }

            $recordsCode = date('YmdHi');
            $managerId = trim($this->data['manager_id'] ?? '');
            $inspectionStaff = $this->getEnabledInspectionStaff($managerId);
            if (!$inspectionStaff) {
                return $this->rFail('巡检人员不存在或已禁用');
            }
            $notes = trim(strval($this->data['notes'] ?? ''));
            $checkTime = date('Y-m-d H:i:s');

            $insertAll = [];
            foreach ($submittedMap as $itemId => $submittedRow) {
                $rowStatus = intval($submittedMap[$itemId]['check_status'] ?? 0);
                $rowNotes = strval($submittedMap[$itemId]['notes'] ?? '');
                $insertAll[] = [
                    'records_code' => $recordsCode,
                    'item_id' => $itemId,
                    'machine_id' => $this->machine['machine_id'],
                    'manager_id' => $managerId,
                    'check_status' => $rowStatus,
                    'check_time' => $checkTime,
                    'notes' => $rowNotes !== '' ? $rowNotes : $notes,
                ];
            }

            Db::startTrans();
            $result = Db::name('check_list_records')->insertAll($insertAll);
            if (!$result) {
                Db::rollback();
                return $this->rFail('检查记录提交失败');
            }
            Db::commit();

            return $this->r(200, 'SUCCESS', [
                'records_code' => $recordsCode,
                'machine_id' => $this->machine['machine_id'],
                'count' => count($insertAll),
            ]);
        } catch (\Throwable $e) {
            Db::rollback();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 获取检查清单记录（按records_code归类）
     * @return array|\think\response\Json
     */
    public function getCheckListRecords()
    {
        try {
            $where = [];
            $where[] = ['cr.machine_id', '=', $this->machine['machine_id']];
            if (!empty($this->data['records_code'])) {
                $where[] = ['cr.records_code', '=', trim($this->data['records_code'])];
            }

            $page = max(1, intval($this->data['page'] ?? 1));
            $pageSize = intval($this->data['pageNum'] ?? ($this->data['pageSize'] ?? 0));
            $total = 0;

            $groupSql = Db::name('check_list_records')
                ->alias('cr')
                ->where($where)
                ->field('cr.records_code')
                ->group('cr.records_code')
                ->fetchSql(true)
                ->select();
            $totalSql = "SELECT COUNT(1) AS tp_count FROM ({$groupSql}) AS t";
            actionLog([
                'machine_id' => $this->machine['machine_id'] ?? '',
                'where' => $where,
                'totalSql' => $totalSql,
            ], 'getCheckListRecords_total_sql');
            $totalRows = Db::query($totalSql);
            $total = intval($totalRows[0]['tp_count'] ?? 0);
            if ($pageSize > 0) {
                if ($total <= 0) {
                    return $this->r(200, 'SUCCESS', [
                        'list' => [],
                        'pagination' => [
                            'page' => $page,
                            'pageSize' => 0,
                            'total' => 0,
                            'totalPage' => 0,
                        ],
                    ]);
                }

                $codes = Db::name('check_list_records')->alias('cr')
                    ->where($where)
                    ->field('cr.records_code,max(cr.id) as max_id')
                    ->group('cr.records_code')
                    ->order('max_id desc')
                    ->page($page, $pageSize)
                    ->select()
                    ->column('records_code');

                if (!$codes) {
                    return $this->r(200, 'SUCCESS', [
                        'list' => [],
                        'pagination' => [
                            'page' => $page,
                            'pageSize' => 0,
                            'total' => $total,
                            'totalPage' => 0,
                        ],
                    ]);
                }

                $where[] = ['cr.records_code', 'in', $codes];
            }

            $list = Db::name('check_list_records')
                ->alias('cr')
                ->leftJoin('check_list_items ci', 'ci.id = cr.item_id')
                ->leftJoin('inspection_staff ist', 'ist.staff_id = cr.manager_id')
                ->where($where)
                ->field("cr.id,cr.records_code,cr.item_id,cr.machine_id,cr.manager_id,cr.check_status,cr.check_time,cr.notes,cr.created_at,ci.item_name,ci.description,ci.parent_id,ci.item_level,IFNULL(NULLIF(ist.account_name,''), cr.manager_id) as account_name")
                ->order('cr.records_code desc,cr.id asc')
                ->select()
                ->toArray();

            $grouped = [];
            foreach ($list as $item) {
                $code = $item['records_code'];
                if (!isset($grouped[$code])) {
                    $grouped[$code] = [
                        'records_code' => $code,
                        'machine_id' => $item['machine_id'],
                        'manager_id' => $item['manager_id'],
                        'account_name' => $item['account_name'] ?? '',
                        'nickname' => $item['account_name'] ?? '',
                        'check_time' => $item['check_time'],
                        'records' => [],
                    ];
                }
                $grouped[$code]['records'][] = [
                    'id' => $item['id'],
                    'item_id' => $item['item_id'],
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? '',
                    'check_status' => intval($item['check_status'] ?? 0),
                    'parent_id' => $item['parent_id'],
                    'item_level' => $item['item_level'],
                    'manager_id' => $item['manager_id'],
                    'account_name' => $item['account_name'] ?? '',
                    'nickname' => $item['account_name'] ?? '',
                    'notes' => $item['notes'],
                    'created_at' => $item['created_at'],
                ];
            }

            $result = array_values($grouped);
            if ($pageSize > 0) {
                $currentCount = count($result);
                $responsePageSize = $currentCount;
                return $this->r(200, 'SUCCESS', [
                    'list' => $result,
                    'pagination' => [
                        'page' => $page,
                        'pageSize' => $responsePageSize,
                        'total' => $total,
                        'totalPage' => $responsePageSize > 0 ? (int)ceil($total / $responsePageSize) : 0,
                    ],
                ]);
            }

            return $this->r(200, 'SUCCESS', $result);
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 从文件内容生成并插入 check_list_records
     * 接收参数：file_content (string)，可选参数：per_row (bool)
     * 文档列对应：| check_time | check_status(1/2) | check_list_items.id or item_name | maintainer_id |
     * 根据 check_time 生成 records_code (YmdHi)
     */
    public function importCheckListRecordsFromFile()
    {
        $content = trim(strval($this->data['file_content'] ?? ''));
        $perRow = !empty($this->data['per_row']);
        if ($content === '') {
            return $this->rValidate('file_content不能为空');
        }

        $content = preg_replace('/^```[\s\S]*|[\s\S]*```$/', '', $content);
        $lines = preg_split('/\r?\n/', $content);

        $insertAll = [];
        $errors = [];
        $missingItems = [];
        $machineId = $this->machine['machine_id'] ?? '';

        // 仅处理形如: | 2026-05-15 16:14:47 | 正常 | 电源与系统 | 123 | 的生效行，其他行跳过
        // 列位定义：| check_time | 结果(正常/异常) | check_list_items.id或item_name | maintainer_id |
        $patternCheck = '/^\|\s*(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s*\|\s*(正常|异常|1|2)\s*\|\s*([^|]+?)\s*\|\s*([^|]+?)\s*\|?$/u';
        foreach ($lines as $ln) {
            $ln = trim($ln);
            if ($ln === '') continue;
            if (!preg_match($patternCheck, $ln, $match)) {
                // 非生效格式行跳过
                continue;
            }

            $checkTimeRaw = trim($match[1]);
            $statusCol = trim($match[2]);
            $itemCol = trim($match[3]);
            $maintainerId = trim($match[4]);
            $inspectionStaff = $this->getEnabledInspectionStaff($maintainerId);
            if (!$inspectionStaff) {
                $errors[] = "巡检人员不存在或已禁用: {$maintainerId}";
                continue;
            }

            $ts = strtotime($checkTimeRaw);
            if ($ts === false) {
                $errors[] = "时间格式无效: {$checkTimeRaw}";
                continue;
            }
            $checkTime = date('Y-m-d H:i:s', $ts);
            $recordsCode = date('YmdHi', $ts);

            // check_status 解析
            if ($statusCol === '正常') {
                $checkStatus = 1;
            } elseif ($statusCol === '异常') {
                $checkStatus = 2;
            } else {
                $checkStatus = intval($statusCol);
            }
            if ($checkStatus !== 1 && $checkStatus !== 2) {
                $errors[] = "check_status 格式错误: {$statusCol}";
                continue;
            }

            // item id 解析
            if (is_numeric($itemCol)) {
                $itemId = intval($itemCol);
            } else {
                $itemName = $itemCol;
                $itemId = Db::name('check_list_items')->where('item_name', $itemName)->value('id');
                if (!$itemId) {
                    $missingItems[] = $itemName;
                    continue;
                }
            }

            $insertAll[] = [
                'records_code' => $recordsCode,
                'item_id' => intval($itemId),
                'machine_id' => $machineId,
                'manager_id' => trim($maintainerId),
                'check_status' => $checkStatus,
                'check_time' => $checkTime,
                'notes' => '',
            ];
        }

        if ($missingItems) {
            $missingItems = array_values(array_unique($missingItems));
            return $this->rFail('未找到对应的检查项目: ' . implode(',', $missingItems));
        }
        if (empty($insertAll)) {
            return $this->rFail('没有可插入的记录', ['errors' => $errors]);
        }

        // 构建 SQL 字符串用于日志
        $columns = "`records_code`,`item_id`,`machine_id`,`manager_id`,`check_status`,`check_time`,`notes`";
        $valueTuples = [];
        foreach ($insertAll as $row) {
            $v = [
                addslashes($row['records_code']),
                addslashes($row['item_id']),
                addslashes($row['machine_id']),
                addslashes($row['manager_id']),
                addslashes($row['check_status']),
                addslashes($row['check_time']),
                addslashes($row['notes']),
            ];
            $valueTuples[] = "('" . implode("','", $v) . "')";
        }
        $sql = "INSERT INTO `check_list_records` ({$columns}) VALUES " . implode(' , ', $valueTuples) . ";";

        actionLog(['machine_id' => $machineId, 'sql' => $sql, 'count' => count($insertAll)], 'importCheckListRecordsFromFile_sql_before');

        try {
            Db::startTrans();
            $result = Db::name('check_list_records')->insertAll($insertAll);
            if ($result === false) {
                Db::rollback();
                actionLog(['machine_id' => $machineId, 'sql' => $sql, 'error' => 'insertAll returned false'], 'importCheckListRecordsFromFile_error');
                return $this->rFail('检查记录插入失败');
            }
            Db::commit();

            actionLog(['machine_id' => $machineId, 'sql' => $sql, 'count' => $result], 'importCheckListRecordsFromFile_sql_after');

            return $this->r(200, 'SUCCESS', [
                'count' => $result,
                'errors' => $errors,
            ]);
        } catch (\Throwable $e) {
            Db::rollback();
            actionLog(['machine_id' => $machineId, 'sql' => $sql, 'exception' => $e->getMessage()], 'importCheckListRecordsFromFile_exception');
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 固定检查清单一级节点
     * @param array $tree
     * @return array
     */
    protected function mergeDefaultCheckListRoots($tree)
    {
        $defaultNames = ['基础状态', '商品陈列', '核心功能'];
        $rootByName = [];
        $otherRoots = [];
        foreach ($tree as $node) {
            $name = trim(strval($node['item_name'] ?? ''));
            if (in_array($name, $defaultNames, true)) {
                $rootByName[$name] = $node;
            } else {
                $otherRoots[] = $node;
            }
        }

        $result = [];
        foreach ($defaultNames as $index => $name) {
            if (isset($rootByName[$name])) {
                $result[] = $rootByName[$name];
            } else {
                $result[] = [
                    'id' => 0,
                    'parent_id' => null,
                    'item_name' => $name,
                    'item_level' => 1,
                    'description' => '',
                    'sort_order' => $index + 1,
                    'is_active' => 1,
                    'updated_at' => '',
                    'children' => [],
                ];
            }
        }

        return array_merge($result, $otherRoots);
    }

    /**
     * 校验巡检人员账号是否存在。
     * @return array|\think\response\Json
     */
    public function checkInspectionStaffCode()
    {
        $staffCode = trim((string)($this->data['staff_code'] ?? ''));
        if ($staffCode === '') {
            return $this->r(100, '巡检账号不能为空', [
                'exists' => 0,
                'staff_code' => $staffCode,
            ]);
        }

        $staff = Db::name('inspection_staff')
            ->where(['staff_code' => $staffCode])
            ->field('staff_id,staff_code,account_name,status,expire_time')
            ->find();

        if (!$staff) {
            return $this->r(100, '账户不存在', [
                'exists' => 0,
                'staff_code' => $staffCode,
            ]);
        }

        return $this->r(200, '账户存在', [
            'exists' => 1,
            'staff_id' => intval($staff['staff_id']),
            'staff_code' => $staff['staff_code'],
            'account_name' => $staff['account_name'] ?? '',
            'status' => intval($staff['status'] ?? 0),
            'expire_time' => intval($staff['expire_time'] ?? 0),
        ]);
    }

    protected function getEnabledInspectionStaff($staffId)
    {
        $staffId = intval($staffId);
        if ($staffId <= 0) {
            return [];
        }

        $staff = Db::name('inspection_staff')
            ->where([
                'staff_id' => $staffId,
                'status' => 1,
            ])
            ->field('staff_id,account_name')
            ->find();

        return $staff ?: [];
    }

    /**
     * 设备上报每日流量使用情况
     * 约定：camera_usage = usage - machine_usage
     * @return array|\think\response\Json
     */
    public function reportSimCardMachineUsage()
    {
        try {
            $date = date('Y-m-d', $this->data['date']);
            $ydate = date('Y-m-d', strtotime('-1 day', $this->data['date'] ?? 0));
            $machineUsage = $this->data['machine_usage'] ?? 0;
            $iccid = trim($this->data['iccid'] ?? '');
            if (!$iccid) {
                $iccid = $this->getSimCardInfoValue(['m_id' => $this->machine['m_id']], 'iccid', 'id desc');
            }
            if (!$iccid) {
                return $this->rFail('iccid不能为空');
            }

            $where = [
                'm_id' => $this->machine['m_id'],
                'machine_id' => $this->machine['machine_id'],
                'iccid' => $iccid,
                'date' => $date,
            ];

            $row = $this->getSimCardMachineFind($where);
            $usage = 0;
            $totalUsage = $row['total_usage'] ?? 0;
            $machine_usage = $this->data['machine_usage'] ?? 0;
            if ($row) {
                $usage = $row['usage'] ?? 0;
                
            } else {
                if ($totalUsage > 0) {
                    $prev = $this->getSimCardMachineFind([
                        'iccid' => $iccid,
                        'date' => $ydate
                    ], 'total_usage', 'date desc,id desc');
                    $prevTotal = $prev['total_usage'] ?? 0;
                    $usage = bcsub($totalUsage, $prevTotal, 2);
                    if ($usage < 0) {
                        $usage = 0;
                    }
                }
            }
            //目前软件上报是上行下行一起，包含了摄像头流量
            $cameraUsage = 0;
            // $cameraUsage = bcsub($usage, $machineUsage, 2);
            // if ($cameraUsage < 0) {
            //     $cameraUsage = 0;
            // }
            $resData = [
                'm_id' => $this->machine['m_id'],
                'machine_id' => $this->machine['machine_id'],
                'iccid' => $iccid,
                'date' => $date,
                'total_usage' => $totalUsage,
                'usage' => $usage,
                'machine_usage' => $machine_usage,
                'camera_usage' => 0,
                'remark' => $this->data['remark'] ?? '',
            ];
            if(!$row) {
                $this->addSimCardMachine($resData);
            } else {
                $this->updateSimCardMachine($resData, ['id' => $row['id']]);
            }    
            return $this->r(200, 'SUCCESS', [
                'date' => $date,
                'iccid' => $iccid,
                'usage' => $usage,
                'machine_usage' => $machineUsage,
                'camera_usage' => $cameraUsage,
            ]);
        } catch (\Throwable $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 获取设备预补货详情（按 record_no + machine_id）
     * @return array
     */
    public function getPreReplenishmentDetail()
    {
        $recordNo  = $this->data['record_no'] ?? '';
        $machineId = $this->data['machine_id'] ?? '';

        if (!$recordNo || !$machineId) {
            return $this->rFail('参数错误');
        }

        $order = PreReplenishmentOrderModel::getFind(['record_no' => $recordNo], 'id,record_no');
        if (!$order) {
            return $this->r(100, '单据不存在');
        }

        $details = PreReplenishmentDetailModel::where([
            ['order_id', '=', $order['id']],
            ['machine_id', '=', $machineId],
        ])->order('id asc')->select()->toArray();

        if (!$details) {
            return $this->r(100, '未找到预补货数据');
        }

        // 收集 mc_id 批量查货道获取商品信息
        $mcIds = array_unique(array_column($details, 'mc_id'));
        $channelRows = $this->getMachineChannelList([['mc_id', 'in', $mcIds]]);
        $channelMap = [];
        foreach ($channelRows as $cr) {
            $channelMap[$cr['mc_id']] = $cr;
        }

        // 检查是否已经确认预补货
        $confirmed = false;
        $channels = [];
        foreach ($details as $d) {
            if (($d['order_count'] ?? 0) >= 1) {
                $confirmed = true;
            }
            $mc = $channelMap[$d['mc_id']] ?? [];
            $channels[] = [
                'mc_id'            => $d['mc_id'],
                'channel_code'     => $d['channel_code'],
                'sku'              => $d['sku'],
                'g_id'             => $mc['g_id'] ?? 0,
                'g_name'           => $mc['g_name'] ?? '',
                'pic'              => $mc['pic'] ?? '',
                'capacity'         => $mc['capacity'] ?? 0,
                'stock'            => $mc['stock'] ?? 0,
                'plan_quantity'    => $d['plan_quantity'],
                'actual_quantity'  => $d['actual_quantity'] ?? 0,
                'order_count'      => $d['order_count'] ?? 0,
            ];
        }

        if ($confirmed) {
            return $this->r(100, '您已经预补货了，如需重新补货联系客服处理');
        }

        return $this->r(200, $this->lang("query_success"), ['channels' => $channels]);
    }

    /**
     * 设备确认预补货
     * 记录补货日志、更新货道库存变化
     * @return array
     */
    public function confirmPreReplenishment()
    {
        $recordNo  = $this->data['record_no'] ?? '';
        $machineId = $this->data['machine_id'] ?? '';
        $channel   = json2arr($this->data['channel'] ?? []); // [{mc_id, quantity}]

        if (!$recordNo || !$machineId || !$channel) {
            return $this->rFail('参数错误');
        }

        $order = PreReplenishmentOrderModel::getFind(['record_no' => $recordNo], 'id,record_no,creator_id');
        if (!$order) {
            return $this->r(100, '单据不存在');
        }

        $anyDetail = PreReplenishmentDetailModel::where([
            ['order_id', '=', $order['id']],
            ['machine_id', '=', $machineId],
            ['order_count', '>=', 1],
        ])->find();
        if ($anyDetail) {
            return $this->r(100, '您已经预补货了，如需重新补货联系客服处理');
        }

        $this->startTrans();
        try {
            foreach ($channel as $item) {
                $mcId     = (int)($item['mc_id'] ?? 0);
                $quantity = (int)($item['quantity'] ?? 0);

                if (!$mcId || $quantity <= 0) {
                    $this->rollbackTrans();
                    return $this->rFail('明细参数不完整');
                }

                $detail = PreReplenishmentDetailModel::where([
                    ['order_id', '=', $order['id']],
                    ['machine_id', '=', $machineId],
                    ['mc_id', '=', $mcId],
                ])->lock(true)->find();

                if (!$detail) {
                    $this->rollbackTrans();
                    return $this->rFail('mc_id ' . $mcId . ' 不在预补货范围内');
                }

                $newActual = ($detail['actual_quantity'] ?? 0) + $quantity;
                if ($newActual > $detail['plan_quantity']) {
                    $this->rollbackTrans();
                    return $this->rFail('货道 mc_id ' . $mcId . ' 补货数量超过预补数量');
                }

                PreReplenishmentLogModel::create([
                    'record_no'    => $recordNo,
                    'm_id'         => $this->machine['m_id'] ?? 0,
                    'machine_id'   => $machineId,
                    'channel_code' => $detail['channel_code'],
                    'sku'          => $detail['sku'],
                    'quantity'     => $quantity,
                    'report_time'  => date('Y-m-d H:i:s'),
                    'raw_payload'  => arr2json($this->data),
                ]);

                $mc = $this->getMachineChannelFind(['mc_id' => $mcId]);
                if ($mc) {
                    $newStock = ($mc['stock'] ?? 0) + $quantity;
                    if ($newStock > ($mc['capacity'] ?? 0)) {
                        $this->rollbackTrans();
                        return $this->rFail('货道 mc_id ' . $mcId . ' 补货后库存超过容量限制(' . $mc['capacity'] . ')');
                    }
                    $this->setMachineChannelInc(['mc_id' => $mc['mc_id']], 'stock', $quantity);
                    $this->addGoodsChange([
                        'm_id'         => $this->machine['m_id'],
                        'machine_id'   => $machineId,
                        'machine_name' => $this->machine['machine_name'] ?? '',
                        'mc_id'        => $mc['mc_id'],
                        'channel_code' => $mc['channel_code'],
                        'mg_id'        => $mc['mg_id'] ?? 0,
                        'g_id'         => $mc['g_id'],
                        'g_name'       => $mc['g_name'],
                        'gc_id'        => $mc['gc_id'],
                        'gc_name'      => $mc['gc_name'],
                        'pic'          => $mc['pic'],
                        'sku'          => $detail['sku'],
                        'bar_code'     => $mc['bar_code'] ?? '',
                        'change_value' => $quantity,
                        'ao_id'        => $this->machine['ao_id'],
                        'creator'      => $order['creator_id'] ?? '',
                        'desc'         => '预补货上架',
                        'position'     => 1,
                        'type'         => 2,
                    ]);
                    $this->addMachineChannelReplenishment([
                        'm_id'         => $this->machine['m_id'],
                        'machine_id'   => $machineId,
                        'machine_name' => $this->machine['machine_name'] ?? '',
                        'mc_id'        => $mc['mc_id'],
                        'channel_code' => $mc['channel_code'],
                        'mg_id'        => $mc['mg_id'] ?? 0,
                        'g_id'         => $mc['g_id'],
                        'g_name'       => $mc['g_name'],
                        'gc_id'        => $mc['gc_id'],
                        'gc_name'      => $mc['gc_name'],
                        'pic'          => $mc['pic'],
                        'sku'          => $detail['sku'],
                        'bar_code'     => $mc['bar_code'] ?? '',
                        'batch_number' => $mc['batch_number'] ?? '',
                        'before'       => $mc['stock'] ?? 0,
                        'quantity'     => $quantity,
                        'after'        => $newStock,
                        'rep_type'     => 1,//上架补货
                        'creator'      => $order['creator_id'] ?? 0,
                        'ao_id'        => $this->machine['ao_id'] ?? 0,
                        'create_time'  => time(),
                    ]);
                }

                $compareStatus = $this->resolveCompareStatus($detail['plan_quantity'], $newActual);
                PreReplenishmentDetailModel::update([
                    'id'                  => $detail['id'],
                    'actual_quantity'     => $newActual,
                    'actual_sku'          => $detail['sku'],
                    'actual_channel_code' => $detail['channel_code'],
                    'compare_status'      => $compareStatus,
                    'order_count'         => Db::raw('order_count + 1'),
                ]);
            }

            $this->refreshOrderBizStatus($order['id']);
            $this->commitTrans();
            return $this->r(200, '预补货确认成功');
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

	/**
     * 设备提交客户退货日志。
     * 普通编码匹配当前设备订单号后四位；特殊编码仅跳过订单校验，不触发实际退款。
     */
    public function submitRefundGoodsLog()
    {
        $inputCode = trim((string)$this->data['input_code']);
        $specialCode = trim((string)config('refund_goods.special_code')) ?? '0000';
        $isSpecialCode = $specialCode !== ''
            && preg_match('/^\d{4}$/', $specialCode)
            && hash_equals($specialCode, $inputCode);

        $order = null;
        if (!$isSpecialCode) {
            $order = Db::name('sale_orders')
                ->where('m_id', intval($this->machine['m_id']))
                ->whereLike('trade_no', '%' . $inputCode)
                ->field('order_id,trade_no')
                ->order('order_id desc')
                ->find();
            if (!$order) return $this->r(300, '未找到订单号后四位匹配的当前设备订单');
        }

        $verifyStatus = ($isSpecialCode || $order) ? 1 : 2;
        $insert = [
            'm_id' => intval($this->machine['m_id']),
            'machine_id' => $this->machine['machine_id'],
            'ao_id' => intval($this->machine['ao_id']),
            'order_id' => intval($order['order_id'] ?? 0),
            'trade_no' => $order['trade_no'] ?? '',
            'mobile' => trim((string)$this->data['mobile']),
            'input_code' => $inputCode,
            'verify_type' => $isSpecialCode ? 2 : 1,
            'verify_status' => $verifyStatus,
            'pic_out_goods_box' => trim((string)$this->data['pic_out_goods_box']),
            'video_out_goods_box' => trim((string)$this->data['video_out_goods_box']),
            'video_refund_goods' => trim((string)$this->data['video_refund_goods']),
        ];
        $logId = $this->addMachineRefundGoodsLog($insert);

        $result = [
            'mrgl_id' => intval($logId),
            'verify_type' => $insert['verify_type'],
            'verify_status' => $verifyStatus,
            'order_id' => $insert['order_id'],
            'trade_no' => $insert['trade_no'],
        ];
        if (!$logId) {
            return $this->r(300, '退货日志记录失败', $result);
        }
        if (!$isSpecialCode && !$order) {
            return $this->r(300, '未找到订单号后四位匹配的当前设备订单', $result);
        }
        return $this->r(200, '退货日志记录成功', $result);
    }
}
