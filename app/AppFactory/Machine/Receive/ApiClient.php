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
use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementPushTrait;
use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementRecordTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrganizationTrait;
use app\AppFactory\Kernel\Traits\Config\ConfigTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryLangTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryTrait;
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
        AdvertisementPushTrait,
        AdvertisementRecordTrait,
        AuthOrganizationTrait,
        AuthManagerTrait,
        ConfigTrait,
        GoodsTrait,
        GoodsLangTrait,
        GoodsCategoryLangTrait,
        GoodsCategoryTrait,
        MachineViewTrait,
        MachineConfigTrait,
        MachineInfoTrait,
        MachineChannelTrait,
        MachineChannelReplenishmentTrait,
        MachineVersionPlanTrait,
        MachineGoodsTrait,
        MachineHelpTrait,
        TemplateViewTrait,

        BeforeOrderPaymentTrait,
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
        if (!$this->machine['manager_id'] && !$this->machine['tally_clerk'])
            return $this->rFail($this->lang("VLogin.not_manager"));
        $where[] = ['manager_id',"in",[$this->machine['manager_id'],$this->machine['tally_clerk']]];
        $where['account'] = $this->data['account'];
        $manager = $this->getAuthManagerFind($where,'manager_id,nickname,account,pic,password,status');
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
        return $this->r("200", "SUCCESS", $this->getMachineGoodsList($where, $this->data['pageNum'] ?? 0, $goodsField));
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
        $channelField = "mc_id,m_id,machine_id,channel_code,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,length,width,height,
        cost_price,market_price,retail_price,x_axis,y_axis,shelf_way,
        slot_hole,capacity,stock,is_gift,is_recommend,stock_warning,recoverable,heat,channel_position,fetch_mode,status";
        return $this->r(200, "SUCCESS", $this->getMachineChannelList($where, 0, $channelField));
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
        }
        // 查询新商品，修改货道商品信息，重置库存为新数量，生成新的补货记录
        $g = $this->getGoodsFind(['g_id' => $this->data['g_id']],'g_id,g_name,gc_id,gc_name,pic,sku,bar_code,cost_price,market_price,retail_price');
        $g = obj2arr($g);
        if (!$g) {
            $this->rollbackTrans();
            return $this->rFail($this->lang("VChangeChannelGoods.goods_no_data"));
        }
        $mg = $this->getMachineGoodsFind(['g_id' => $g['g_id'],'m_id' => $this->machine['m_id']],'mg_id');
        if ($mg) {
            $mc['mg_id'] = $mg['mg_id'];
        }
        $mc = array_merge($mc,$g);
        if (isset($this->data['quantity']) && $this->data['quantity'] > 0) {
            if ($this->data['quantity'] > $mc['capacity']) {
                $this->rollbackTrans();
                return $this->rFail($this->lang("VChannelReplenishment.exceed_capacity_limit"));
            }
            $mc['stock'] = $this->data['quantity'];
            $repNewData = $this->handleRepData($mc,$this->data['quantity']);
            $flag[] = $this->addMachineChannelReplenishment($repNewData);
        }
        $flag[] = $this->updateMachineChannel($mc);
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
        $where['mc_id'] = $this->machine['config_id'];
        $configField = "mc_id,mc_title,buy_flow,qr_code,qr_desc, tax_switch,tax_name,tax_rate,limit_quantity,limit_amount,
        pay_type,unionpay_terminal_number,scan_pick_up,email_lang,buy_channel,preclaim,random_pickup,more_out,member_login,door_video,
        face_identification,pre_loading,printer_disable,note_model,receipt,receipt_code1,receipt_code2,receipt_code3,receipt_desc,result_receipt,
        deal_success_title,deal_success_sub_title,deal_abnormal_pic,deal_fail_title,deal_fail_sub_title,deal_service_phone,terminal_timeout,volume,
        show_goods,show_goods_view,goods_sort,cabinet_tray_rotation,cabinet_light,light_effect,claim_goods_title,out_goods_title,discount_show,
        discount_pic,stock_warning,expire_notice";
        return $this->rQ($this->getMachineConfigFind($where, $configField));
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
        $where[] = ['mh_id', 'in',explode(",",$this->machine['help_id'])];
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

    /**
     * 获取设备归属组织所有上级商品
     * @return array|string
     */
    public function goods()
    {
        $goodsList = [];
        $aoIds = $this->getPathIds($this->machine["ao_id"], 1);
        if ($aoIds) {
            $goodsList = $this->getGoodsList([['ao_id', 'in', $aoIds]], $this->data['pageNum'] ?? 0, '*', 'update_time desc');
        }
        return $this->rQ($goodsList);
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
        $trade_no = date("YmdHis") . $this->machine['m_id'] . $this->get_rand_string(6, "num");
        $order = [
            "trade_no" => $trade_no,
            "m_id" => $this->machine['m_id'],
            "machine_name" => $this->machine['machine_name'],
            "machine_id" => $this->machine['machine_id'],
            "manager_id" => $this->machine['manager_id'],
            "pay_type" => $this->data['pay_type'],
            "pay_method" => $this->data['pay_method'],
            "create_date" => strtotime(date("Y-m-d")),
        ];
        $updateOrder = [];
        $this->startTrans();
        $order_id = $this->addSaleOrders($order);
        if ($order_id) {
            $updateOrder['order_id'] = $order_id;
            $updateOrder['cost_price'] = 0;
            $updateOrder['market_price'] = 0;
            $updateOrder['retail_price'] = 0;
            $updateOrder['quantity'] = 0;
            $updateOrder['total_price'] = 0;
            $updateOrder['total_quantity'] = 0;
            $this->data['carList'] = json2arr($this->data['carList']);
            foreach ($this->data['carList'] as $key => $value) {
                $mc = $this->getMachineChannelFind(['mc_id' => $value['mc_id']]);
                if (!$mc) return $this->r(100, $this->lang("VSubCar.channel_no_data"));
                $details = [
                    "order_id" => $order_id,
                    "mc_id" => $mc['mc_id'],
                    "channel_position" => $mc['channel_position'],
                    "channel_code" => $mc['channel_code'],
                    "mg_id" => $mc['mg_id'],
                    "g_id" => $mc['g_id'],
                    "g_name" => $mc['g_name'],
                    "pic" => $mc['pic'],
                    "sku" => $mc['sku'],
                    "gc_id" => $mc['gc_id'],
                    "gc_name" => $mc['gc_name'],
                    "cost_price" => $mc['cost_price'],
                    "market_price" => $mc['market_price'],
                    "retail_price" => $mc['retail_price'],
                    "total_sod_price" => bcmul($mc['retail_price'], $value['quantity'], 2),
                    "quantity" => $value['quantity'],
                    "bar_code" => $mc['bar_code'],
                    "batch_number" => $mc['batch_number'],
                    "manufacture_time" => $mc['manufacture_time'],
                    "sell_by_date" => $mc['sell_by_date'],
                ];
                $sod_id = $this->addSaleOrdersDetails($details);
                if ($sod_id) {
                    $updateOrder['cost_price'] = bcadd($updateOrder['cost_price'], bcmul($mc['cost_price'], $value['quantity'], 2), 2);
                    $updateOrder['market_price'] = bcadd($updateOrder['market_price'], bcmul($mc['market_price'], $value['quantity'], 2), 2);
                    $updateOrder['retail_price'] = bcadd($updateOrder['retail_price'], bcmul($mc['retail_price'], $value['quantity'], 2), 2);
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
            $flag[] = $this->countIncome();
            actionLog($this->getLS(),'修改订单SQL');
            $result = $this->checkFlag($flag);
            actionLog($result,'事务结果');
            if ($result) {
                $this->commitTrans();
                return $this->r(200,$this->lang("VSubCar.make_order_success"),['order' => $this->order]);
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
}