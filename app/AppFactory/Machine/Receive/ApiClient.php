<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/23
 * Time: 9:43
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityMachineTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickCodeTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickTrait;
use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementPushTrait;
use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementRecordTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerMachineTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrganizationTrait;
use app\AppFactory\Kernel\Traits\Config\ConfigTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryLangTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsCornerTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsLangTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelReplenishmentTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineConfigTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineHelpTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineInfoTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineVersionPlanTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineViewTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyIncomeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyManagerTrait;
use app\AppFactory\Kernel\Traits\Template\TemplateViewTrait;

class ApiClient extends ReceiveBaseClient
{
    use
        ActivityCouponTrait,ActivityCouponUsedTrait,ActivityGoodsTrait,ActivityMachineTrait,
        ActivityPickTrait,ActivityPickCodeTrait,
        AdvertisementPushTrait,
        AdvertisementRecordTrait,
        AuthOrganizationTrait,
        AuthManagerTrait,
        AuthManagerMachineTrait,
        ConfigTrait,
        GoodsTrait,
        GoodsLangTrait,
        GoodsCategoryLangTrait,
        GoodsCategoryTrait,
        GoodsCornerTrait,
        MachineViewTrait,
        MachineConfigTrait,
        MachineInfoTrait,
        MachineChannelTrait,
        MachineChannelReplenishmentTrait,
        MachineVersionPlanTrait,
        MachineGoodsTrait,
        MachineHelpTrait,
        TemplateViewTrait,

        BeforeOrderPaymentTrait,AfterOrderPaymentTrait,
        SaleOrdersTrait,
        SaleOrdersRevenueTrait,
        StrategyIncomeTrait,
        StrategyManagerTrait
        ;

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->dataRecord();
    }

    protected $order;

    /**
     * 登录验证
     * @return array|string
     */
    public function login()
    {
        $where['m_id'] = $this->machine['m_id'];
        $manager_id = $this->getAuthManagerMachineValue(['account' => $this->data['account'],'m_id' => $this->machine['m_id']],'manager_id');
        if (!$manager_id) {
            return $this->rFail($this->lang("VLogin.not_manager"));
        }
        $manager = $this->getAuthManagerFind(['manager_id' => $manager_id],'manager_id,nickname,account,pic,password,status');
        if (!$manager) return $this->rFail($this->lang("VLogin.account_pwd_error"));
        $manager = $manager->toArray();
        if ($manager['password'] != md5($this->data['password'] . config("app.salt")))
            return $this->rFail($this->lang("VLogin.account_pwd_error"));
        if ($manager['status'] == 2) return $this->rFail($this->lang("VLogin.account_disabled"));
        unset($manager['password'],$manager['status']);
        return $this->r(200,$this->lang("VLogin.login_success"),$manager);
    }

    /**
     * 退出登录
     * @return array|string
     */
    public function logout()
    {
        return $this->r(200,$this->lang("VLogin.logout_success"));
    }

    /**
     * 获取系统配置信息
     * @return array|string
     */
    public function systemInfo()
    {
        $pIds = $this->getParentIdList($this->machine['manager_id']);
        $pIds[] = $this->machine['manager_id'];
        $systemInfo = $this->getConfigContent([['creator','in',$pIds],"config_switch" => 1,'config_name' => "systemInfo"]);
        return $this->rQ($systemInfo);
    }

    /**
     * 查询设备信息
     * @return array|string
     */
    public function machine()
    {
        return $this->r(200, 'SUCCESS', $this->machine);
    }

    /**
     * 设备商品信息
     * @return array|string
     */
    public function machineGoods()
    {
        $where['m_id'] = $this->machine['m_id'];
        $goodsField = "mg_id,m_id,machine_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price,available_stock,
        disabled_stock,reserve_stock,standby_stock,pre_loading_stock,is_shelf";
        return $this->r(200, "SUCCESS", $this->getMachineGoodsList($where, $this->data['pageNum'] ?? 0, $goodsField));
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
        $channelField = "mc_id,m_id,machine_id,channel_code,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,length,width,height,
        cost_price,market_price,retail_price,x_axis,y_axis,shelf_way,
        slot_hole,capacity,stock,is_gift,is_recommend,stock_warning,recoverable,heat,channel_position,fetch_mode,status";
        $mcList = $this->getMachineChannelList($where, 0, $channelField,'channel_code asc');
        if ($mcList) {
            $mcList = $mcList->toArray();
            foreach ($mcList as $key => $mc) {
                $where = [];
                $where[] = ['gc.start_time',"<=",time()];
                $where['ag.g_id'] = $mc['g_id'];
                $where['am.m_id'] = $mc['m_id'];
                $where[] = ['status',"<",3];
                $corner = $this->getGoodsCornerFindByAmAg($where,'gc.id,gc.corner_name,gc.corner_type,gc.pic,gc.style,gc.position,gc.start_time,gc.end_time,gc.status');
                if ($corner) {
                    $updateCorner = [];
                    if ($corner['status'] == 1) {
                        $updateCorner['status'] = 2;
                    }
                    if ($corner['end_time'] > 0 && $corner['end_time'] < time()) {
                        $updateCorner['status'] = 3;
                        $corner = [];
                    }
                    if ($updateCorner) {
                        $updateCorner['id'] = $corner['id'];
                        $this->updateGoodsCorner($updateCorner);
                    }
                }
                $mc['corner'] = $corner;
                $mcList[$key] = $mc;
            }
        }
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
        $this->startTrans();
        // 清空旧商品库存，生成退货记录
        $mc = $this->getMachineChannelFind(['mc_id' => $this->data['mc_id']]);
        $mc = obj2arr($mc);
        if ($mc['stock'] > 0) {
            $repData = $this->handleRepData($mc, bcsub(0, $mc['stock']));
            $flag[] = $this->addMachineChannelReplenishment($repData);
            $mc['stock'] = 0;
        }
        if (isset($this->data['capacity']) && $this->data['capacity']) {
            $mc['capacity'] = $this->data['capacity'];
        }
        if ($this->data['mg_id']) {
            // 查询新商品，修改货道商品信息，重置库存为新数量，生成新的补货记录
            $mg = $this->getMachineGoodsFind(['mg_id' => $this->data['mg_id']],
                'mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price,standby_stock');
            if (!$mg) {
                $this->rollbackTrans();
                return $this->rFail($this->lang("VChangeChannelGoods.mg_no_data"));
            }
            $mg = $mg->toArray();
            if ($mg['standby_stock'] > 0 && $this->data['quantity'] > $mg['standby_stock']) {
                $this->rollbackTrans();
                return $this->rFail($this->lang("VChangeChannelGoods.mg_stock_out"));
            }
            if (isset($this->data['quantity']) && $this->data['quantity'] > 0) {
                if ($this->data['quantity'] > $mc['capacity']) {
                    $this->rollbackTrans();
                    return $this->rFail($this->lang("VChannelReplenishment.exceed_capacity_limit"));
                }
                $mc['stock'] = $this->data['quantity'];
                $repNewData = $this->handleRepData($mc,$this->data['quantity']);
                $flag[] = $this->addMachineChannelReplenishment($repNewData);
                if ($mg['standby_stock'] > 0)
                    $flag[] = $this->updateMachineGoods(['mg_id' => $mg['mg_id'],'standby_stock' => bcsub($mg['standby_stock'],$this->data['quantity'])]);
            }
            unset($mg['standby_stock']);
            $mc = array_merge($mc,$mg);
        }
        $flag[] = $this->updateMachineChannel($mc);
        actionLog($this->getLS(),'【SQL】修改货道信息');
        $result = $this->checkFlag($flag);
        $result ? $this->commitTrans(): $this->rollbackTrans();
        return $this->rAction($result);
    }

    /**
     * 设备配置信息
     * @return array|string
     */
    public function machineConfig()
    {
        $where["m_id"] = $this->machine['m_id'];
        $configField = "mc_id,mc_title,buy_flow,qr_code,qr_desc, tax_switch,tax_name,tax_rate,limit_quantity,limit_amount,
        pay_type,unionpay_terminal_number,scan_pick_up,email_lang,buy_channel,preclaim,random_pickup,more_out,member_login,door_video,
        face_identification,pre_loading,printer_disable,note_model,receipt,receipt_code1,receipt_code2,receipt_code3,receipt_desc,result_receipt,
        deal_success_title,deal_success_sub_title,deal_abnormal_pic,deal_fail_title,deal_fail_sub_title,deal_service_phone,terminal_timeout,volume,
        show_goods,show_goods_view,goods_sort,cabinet_tray_rotation,cabinet_light,light_effect,claim_goods_title,out_goods_title,discount_show,
        discount_pic,stock_warning,expire_notice";
        $data = $this->getMachineConfigFind($where, $configField);
        actionLog($this->getLS());
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
        $where["m_id"] = $this->machine['machine_id'];
        $helpField = "mh_id,show,title,content,lang";
        return $this->rQ($this->getMachineHelpList($where, 0, $helpField));
    }

    /**
     * 设备首页模板视图
     * @return array|string
     */
    public function machineView()
    {
        $where['m_id'] = $this->machine['m_id'];
        $where['status'] = 1;
        $mv = $this->getMachineViewFind($where,'mv_id,view_id,m_id,machine_id,name,notes,publish_time,expire_time','mv_id desc');
        if ($mv) {
            $mv['details'] = $this->getTemplateViewFind(['id' => $mv['view_id']],'
                name,height,width,plugin_data
            ');
            return $this->r(200,'SUCCESS',$mv);
        }
        return $this->r(100,$this->lang("query_mv_no_data"));
    }

    protected $goodsField = "g.g_id,g.g_name,g.gc_id,g.gc_name,g.model,g.pic,g.sku,g.bar_code,g.sku2,g.manufacturer,g.service_phone,g.performance,g.sell_channel,g.is_gift,g.is_recommend,g.recoverable,g.heat,g.release_time,
            g.length,g.width,g.height,g.group_quantity,g.status,g.ao_id,g.update_time,g.desc,
            mg.mg_id,mg.cost_price,mg.market_price,mg.retail_price,mg.available_stock,mg.disabled_stock,mg.reserve_stock,mg.standby_stock,mg.pre_loading_stock,mg.is_shelf";
    /**
     * 获取设备归属组织所有上级商品
     * @return array|string
     */
    public function goods()
    {
        $goodsList = [];
        $aoIds = $this->getPathIds($this->machine["ao_id"], 1);
        if ($aoIds) {
            $goodsList = $this->getGoodsJoinMachineGoodsList([['ao_id', 'in', $aoIds]], $this->data['pageNum'] ?? 0, $this->goodsField, 'g.update_time desc',$this->machine['m_id']);
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
        $goods = $this->getGoodsJoinMachineGoodsFind(["g.g_id" => $this->data['g_id']], $this->goodsField, 'g.update_time desc');
        if (is_string($goods)) return $this->rFail($goods);
        if ($goods) {
            $goods['lang'] = $this->getGoodsLangList(['g_id' => $this->data['g_id']], 0, 'g_name,gc_name,manufacturer,desc,performance,lang');
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
        $where['machine_id'] = $this->machine['machine_id'];
        $where[] = ['status', "<", 3];
        $where[] = ['start_date', '<=', time()];
        $field = "adv_id,adv_title,res_id,res_title,file_path,type,duration_time,total_times,play_times,remain_times,start_date,end_date,start_time,end_time,position,screen,screen_full,status";
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
        $field = "adv_id,adv_title,res_id,res_title,type,type,duration_time,total_times,play_times,remain_times,m_id,machine_id,position,screen,screen_full";
        $adv = $this->getAdvertisementPushFind($where,$field);
        if (!$adv) return $this->rFail($this->lang("VAdvertisement.adv_no_data"));
        if ($adv['remain_times'] > 0) {
            $adv = $adv->toArray();
            $adv['remain_times']--;  // 剩余次数减1
            $adv['play_times']++;    // 播放次数加1
            $insert = $adv;
            if ($adv['remain_times'] <= 0) {
                $adv['status'] = 3;
            }
            $this->startTrans();
            $flag[] = $this->updateAdvertisementPush($adv);
            $insert['play_time'] = $this->data['play_time'];
            $flag[] = $this->addAdvertisementRecord($insert);
            $result = $this->checkFlag($flag);
            $check = $this->checkTrans($result,0);
            if ($check) {
                return $this->r(200,$this->lang("action_success"),['adv' => $adv]);
            }
            return $this->rFail($this->lang("action_fail"));
        }
        return $this->r(200,$this->lang("VAdvertisement.adv_complete"));
    }

    /**
     * 提交购物车生成订单信息
     * @return array|string
     * @throws \Exception
     */
    public function subCar()
    {
        if ($this->data['pay_type'] != 4 && $this->data['pay_type'] != 0) return $this->rFail($this->lang("VSubCar.pay_type_no_range"));
        $trade_no = date("YmdHis") . $this->machine['m_id'] . $this->get_rand_string(6, "num");
        $order = [
            "trade_no" => $trade_no,
            "m_id" => $this->machine['m_id'],
            "machine_name" => $this->machine['machine_name'],
            "machine_id" => $this->machine['machine_id'],
            "manager_id" => $this->machine['manager_id'],
            "ao_id" => $this->machine['ao_id'],
            "pay_type" => $this->data['pay_type'],
            "pay_method" => $this->data['pay_method'],
            "create_date" => strtotime(date("Y-m-d")),
        ];
        $updateOrder = [];
        $this->startTrans();
        $order_id = $this->addSaleOrders($order);
        if ($order_id) {
            // 取货码活动
            if (isset($this->data['pick_code'])) {
                $updateOrder = $this->orderUsePickCode($trade_no,$order_id);
                if (is_string($updateOrder)) {
                    $this->rollbackTrans();
                    return $this->rFail($updateOrder);
                }
                $updateOrder['mch_no'] = $trade_no;
            }
            $updateOrder['order_id'] = $order_id;
            $updateOrder['cost_price'] = 0;
            $updateOrder['market_price'] = 0;
            $updateOrder['retail_price'] = 0;
            $updateOrder['quantity'] = 0;
            $updateOrder['total_price'] = 0;
            $updateOrder['total_quantity'] = 0;
            if (!isset($this->data['carList']) || !$this->data['carList']) {
                $this->rollbackTrans();
                return $this->rFail("购物车不能为空");
            }
            $this->data['carList'] = json2arr($this->data['carList']);
            foreach ($this->data['carList'] as $key => $value) {
                $mc = $this->getMachineChannelFind(['mc_id' => $value['mc_id']]);
                if (!$mc) {
                    $this->rollbackTrans();
                    return $this->r(100, $this->lang("VSubCar.channel_no_data"));
                }
                if (!$mc['mg_id']) {
                    $this->rollbackTrans();
                    return $this->r(100,$this->lang("VSubCar.mg_id_require"));
                }
                $mg = $this->getMachineGoodsFind(['mg_id' => $mc['mg_id']],'g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price');
                if ($this->data['pay_type'] == 0) {
                    $mc['retail_price'] = 0;
                }
                $details = [
                    "order_id" => $order_id,
                    "mc_id" => $mc['mc_id'],
                    "shelf_way" => $mc['shelf_way'],
                    "channel_position" => $mc['channel_position'],
                    "channel_code" => $mc['channel_code'],
                    "mg_id" => $mc['mg_id'],
                    "g_id" => $mg['g_id'],
                    "g_name" => $mg['g_name'],
                    "pic" => $mg['pic'],
                    "sku" => $mg['sku'],
                    "gc_id" => $mg['gc_id'],
                    "gc_name" => $mg['gc_name'],
                    "cost_price" => $mg['cost_price'],
                    "market_price" => $mg['market_price'],
                    "retail_price" => $mg['retail_price'],
                    "total_sod_price" => bcmul($mg['retail_price'], $value['quantity'], 2),
                    "quantity" => $value['quantity'],
                    "bar_code" => $mg['bar_code'],
//                    "batch_number" => $mg['batch_number'],
//                    "manufacture_time" => $mc['manufacture_time'],
//                    "sell_by_date" => $mc['sell_by_date'],
                ];
                $sod_id = $this->addSaleOrdersDetails($details);
                if ($sod_id) {
                    $updateOrder['cost_price'] = bcadd($updateOrder['cost_price'], bcmul($mg['cost_price'], $value['quantity'], 2), 2);
                    $updateOrder['market_price'] = bcadd($updateOrder['market_price'], bcmul($mg['market_price'], $value['quantity'], 2), 2);
                    $updateOrder['retail_price'] = bcadd($updateOrder['retail_price'], bcmul($mg['retail_price'], $value['quantity'], 2), 2);
                    $updateOrder['quantity'] = bcadd($updateOrder['quantity'], $value['quantity']);
                    $updateOrder['total_price'] = bcadd($updateOrder['total_price'], $updateOrder['retail_price'],2);
                    $updateOrder['total_quantity'] = bcadd($updateOrder['total_quantity'], $value['quantity']);
                } else {
                    $this->rollbackTrans();
                    return $this->r(100,$this->lang("VSubCar.make_order_details_fail"));
                }
            }
        }
        if ($updateOrder) {
            $flag[] = $this->updateSaleOrders($updateOrder);
            $this->order = $this->getSaleOrdersFind(['order_id' => $order_id]);
            $this->order['details'] = $this->getSaleOrdersDetailsList(['order_id' => $order_id],0);
            // 有优惠券码，重新处理订单数据
            if (isset($this->data['coupon_code'])) {
                $this->orderUseCoupon();
            }
            // 订单金额大于0才能执行分润
            if ($this->order['total_price'] > 0) {
                $flag[] = $this->countIncome();
            }

            actionLog($this->getLS(),'修改订单SQL');
            $result = $this->checkFlag($flag);
            actionLog($result,'事务结果');
            if ($result) {
                // 免费的直接出货
                if ($this->data['pay_type'] == 0) {
                    $this->outGoods();
                    $this->commitTrans();
                    return $this->r(200,$this->lang("VSubCar.goods_outing"));
                } else {
                    $this->commitTrans();
                    return $this->r(200, $this->lang("VSubCar.make_order_success"), ['order' => $this->order]);
                }
            }
        }
        $this->rollbackTrans();
        return $this->r(100,$this->lang("VSubCar.make_order_fail"));
    }

    /**
     * 获取最新一条设备软件更新计划
     * @return array|string
     */
    public function machineVersionPlan()
    {
        $where['m_id'] = $this->machine['m_id'];
        $where[] = ['publish_time',"<",time()];
        $where['status'] = 1;
        return $this->rQ($this->getMachineVersionPlanFind($where,'mv_id,version_no,path,desc,size','mvp_id desc'));
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
        $params['sign'] = $this->makeSign($params);
        $url = $this->getUrl("/mobile/common/checkScan.html") . "?" . http_build_query($params) ;
        return $this->rQ(['url' => $url]);
    }

}