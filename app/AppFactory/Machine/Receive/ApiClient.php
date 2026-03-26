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
use app\AppFactory\Kernel\Traits\Machine\MachineConfigLangTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineConfigTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineHelpTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineInfoTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineLangTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineOnOffTrait;
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
use app\AppFactory\Kernel\Traits\Template\TemplateViewTrait;
use app\AppFactory\Kernel\Traits\Wx\WxOfficialLoginTrait;
use app\AppFactory\Kernel\Traits\Wx\WxOfficialTrait;
use app\machine\validate\VReceive;
use think\facade\View;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderRefundTrait;
use app\AppFactory\Kernel\Traits\Card\CardTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcBaseTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcGoodsTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrgMachineChannelTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRefundTrait;

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
        MachineTrait,
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
        AuthOrgMachineChannelTrait,
        SaleOrdersRefundTrait;


    public $card_retail_price = 0.01;
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
        $manager = $this->getAuthManagerFind(['manager_id' => $manager_id], 'manager_id,pid,nickname,account,pic,password,status,ao_id');
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
                "ao_id" => $mc['ao_id'],
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
                    'mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price,standby_stock,is_shelf,ao_id'
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
                $mc['manufacture_time'] = "";
                $mc['sell_by_date'] = "";
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
                [['ao_id', 'in', $aoIds]],
                $this->data['pageNum'] ?? 0,
                $this->goodsField,
                'g.update_time desc',
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
        return $this->rQ($advList);
    }

    /**
     * 上报播放广告，adv_id, play_time
     * @return array|string
     */
    public function playAdv()
    {
        $where['adv_id'] = $this->data['adv_id'];
        $field = "adv_id,adv_title,res_id,res_title,type,type,duration_time,total_times,play_times,remain_times,m_id,machine_id,push_type,position,screen,screen_full,ao_id";
        $adv = $this->getAdvertisementPushFind($where, $field);
        if (!$adv) return $this->rFail($this->lang("VAdvertisement.adv_no_data"));
        $adv = $adv->toArray();
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
                            if ($wc_goods_local['g_id'] != '9999' && $wc_goods_local['g_id'] != '0') {
                                $machine_channel = $this->getMachineChannelFind(['g_id' => $wc_goods_local['g_id'], 'm_id' => $this->machine['m_id']]);
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
                                'need_local_out_goods' => $wc_goods_local['g_id'] ? 1 : 0, //是否需要本机出货  0-否 1-是
                                'out_goods_status' => 0, //出货状态  need_local_out_goods = 1时生效  0-未出货   1-已出货
                                'real_channel_code' => $real_channel_code, //实际出货货道
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
                    if (!isset($value['channel_code']) && !$mc['mg_id']) {
                        $this->rollbackTrans();
                        return $this->r(300, $this->lang("VSubCar.mg_id_require"));
                    }
                    if ($value['channel_code'] != 'Z10' && $mc['status'] != 1) {
                        $this->rollbackTrans();
                        return $this->r(300, $this->lang("VSubCar.channel_status_no_3"));
                    }
                    if ($value['channel_code'] != 'Z10' &&  ($mc['stock'] < $value['quantity'])) {
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
                            'ao_id' => $mc['ao_id'],
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
                            return $this->r(100, $this->lang("VSubCar.make_order_details_fail"));
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
        $pay_type_list = [0 => "免支付", 1 => "微信", 2 => "支付宝", 3 => "未定义", 4 => "京东收银", 5 => "会员", 6 => "丽呈线上", 7 => "机器人线上", 8 => "COGOLINK", 9 => "商场积分支付"];
        $pay_method_list = [0 => "免支付", 1 => "扫码支付", 2 => "付款码支付", 3 => "POS机支付", 4 => "商场积分支付"];
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
            'pay_type' => $pay_type_list[$order['pay_type']] . ($order['pay_method'] > 0 ? "-" . $pay_method_list[$order['pay_method']] : ""),
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

    public function requireOutGoods()
    {
        $order = $this->getSaleOrdersFind(['trade_no' => $this->data['trade_no']]);
        if (!$order) return $this->r(300, $this->lang("VSaleOrders.order_not_data"));
        $this->order = is_object($order) ? (method_exists($order,'toArray') ? $order->toArray() : (array)$order) : $order;

        $details = $this->order['details'] ?? $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']])->toArray();
        if (!$details || !is_array($details)) {
            return $this->r(100, 'failed', []);
        }

        $contentArr = [];
        $outArr = [];
        $total_points = 0;

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

        $content = [
            'msgType' => 'outGoods',
            'trade_no' => $this->order['trade_no'],
            'main' => $contentArr,
            'outGoods' => $outArr,
            'order_points' => $this->order['total_points'] ?? 0,
        ];

        
        for($i = 0 ; $i < 10; $i++){
            $latest_order = $this->getSaleOrdersFind(['trade_no' => $this->order['trade_no']]);
            actionLog(@obj2arr($latest_order), 'Http兜底方案下发数据结果');
            if($latest_order['out_status'] == 4) break;
            $result = $this->sendToMachine(['machine_id' => $this->order['machine_id']], 'outGoods', $content);
            actionLog(@obj2arr($result), 'Http兜底方案下发数据结果');
            sleep(5);
        }
        
        $this->order['out_status'] = 2;
        $this->order['pay_status'] = 3;
        $this->order['pay_time'] = $this->order['pay_time'] ?: time();
        try {
            $this->updateSaleOrders($this->order);
        } catch (\Exception $e) {
            actionException($e);
        }

        return $this->r(200, 'success', $content);
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
                    $this->addCard(['card_no' => $this->data['card_no']]);
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
     * 获取积分变化类型
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function getCardChangeLogs()
    {
        $card_points_lists = [];
        $new_data = [];
        $total_card_points = 0;
        $card_info = [];
        $bind_id = '';
        try {
            if (isset($this->data['bind_id']) && !empty($this->data['bind_id'])) {
                //先判断当前登录账号登录信息是否绑定了当前传入的卡号，如果为绑定，提示用户绑卡
                if (isset($this->data['card_no']) && !empty($this->data['card_no'])) {
                    $card_info = $this->getCardFind(['card_no' => $this->data['card_no']]);
                    if (!$card_info) {
                        $this->addCard(['card_no' => $this->data['card_no']]);
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
                    $this->addCard(['card_no' => $this->data['card_no']]);
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
        $wcMachineChannelLists = $this->getWcMachineChannelList($where, $pageNum, "*", 'sort asc');
        if ($wcMachineChannelLists) $wcMachineChannelLists = $wcMachineChannelLists->toArray();
        $wcMachineChannelLists = $pageNum ? $wcMachineChannelLists['data'] : $wcMachineChannelLists;
        foreach ($wcMachineChannelLists as &$v) {
            if ($v['gc_id'] == 11) {
                $daysInfo = $this->getWcGoodsColumn(['no' => $v['out_no']], 'daysInfo');
                if ($daysInfo) $v['daysInfo'] = $daysInfo[0] ?? [];
            }
            $v['goods_lists'] = $this->getWcGoodsLocalList(['out_no' => $v['out_no']])->toArray();
        }
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

    public function test()
    {
        $trade_no = $this->data['trade_no'] ?? '';
        $order_id = $this->data['order_id'] ?? 0;
        $sod_id = $this->data['sod_id'] ?? 0;
        $this->order = $this->getSaleOrdersFind(['trade_no' => $trade_no])->toArray();;
        // $this->refund = $this->getSaleOrdersRefundFind(['trade_no' => $trade_no]);
        $this->paymentSuccessful();
        $this->addCardChangeLog();
        // $this->outGoods();
        die();
        // $order = $this->outGoods();
        // return $this->orderSync2Wc($this->order);                    
        $detail = $this->getSaleOrdersDetailsFind(['sod_id' => $sod_id]);
        return $this->orderRefundSync2Wc($this->order, $detail);
    }

}
