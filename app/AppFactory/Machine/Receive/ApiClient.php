<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/23
 * Time: 9:43
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementPushTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrganizationTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryLangTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsCategoryTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsLangTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineConfigTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineHelpTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineInfoTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineViewTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;

class ApiClient extends ReceiveBaseClient
{
    use
        AdvertisementPushTrait,
        AuthOrganizationTrait,
        GoodsTrait,
        GoodsLangTrait,
        GoodsCategoryLangTrait,
        GoodsCategoryTrait,
        MachineViewTrait,
        MachineConfigTrait,
        MachineInfoTrait,
        MachineChannelTrait,
        MachineGoodsTrait,
        MachineHelpTrait,
        SaleOrdersTrait;

    /**
     * 查询设备信息
     * @return array|string
     */
    public function machine()
    {
        return $this->r(200,'SUCCESS',$this->machine);
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
        return $this->r("200","SUCCESS",$this->getMachineGoodsList($where,$this->data['pageNum'] ?? 0,$goodsField));
    }

    /**
     * 设备货道信息
     * @return array|string
     */
    public function machineChannel()
    {
        $where['m_id'] = $this->machine['m_id'];
        $channelField = "mc_id,m_id,machine_id,channel_code,mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code,length,width,height,
        slot_hole,capacity,stock,is_gift,is_recommend,stock_warning,recoverable,heat,channel_position,fetch_mode,status";
        return $this->r(200,"SUCCESS",$this->getMachineChannelList($where,0,$channelField));
    }

    /**
     * 设备配置信息
     * @return array|string
     */
    public function machineConfig()
    {
        $where['m_id'] = $this->machine['m_id'];
        $configField = "mc_id,m_id,machine_id,buy_flow,qr_code,qr_desc, tax_switch,tax_name,tax_rate,limit_quantity,limit_amount,
        pay_type,unionpay_terminal_number,scan_pick_up,email_lang,buy_channel,preclaim,random_pickup,more_out,member_login,door_video,
        face_identification,pre_loading,printer_disable,note_model,receipt,receipt_code1,receipt_code2,receipt_code3,receipt_desc,result_receipt,
        deal_success_title,deal_success_sub_title,deal_abnormal_pic,deal_fail_title,deal_fail_sub_title,deal_service_phone,terminal_timeout,volume,
        show_goods,show_goods_view,goods_sort,cabinet_tray_rotation,cabinet_light,light_effect,claim_goods_title,out_goods_title,discount_show,
        discount_pic,stock_warning,expire_notice";
        return $this->r(200,"SUCCESS", $this->getMachineConfigFind($where,$configField));
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
        return $this->r(200,"SUCCESS",$this->getMachineInfoFind($where,$infoField));
    }

    /**
     * 设备帮助信息
     * @return array|string
     */
    public function machineHelp()
    {
        $where['m_id'] = $this->machine['m_id'];
        $helpField = "mh_id,m_id,machine_id,show,title,content,lang";
        return $this->r(200,'SUCCESS',$this->getMachineHelpList($where,0,$helpField));
    }

    /**
     * 获取设备归属组织所有上级商品
     * @return array|string
     */
    public function goods()
    {
        $goodsList = [];
        $aoIds = $this->getPathIds($this->machine["ao_id"],1);
        if ($aoIds) {
            $goodsList = $this->getGoodsList([['ao_id', 'in', $aoIds]], $this->data['pageNum'] ?? 0, '*', 'update_time desc');
        }
        return $this->rQ($goodsList);
    }

    /**
     * 获取设备广告信息
     * @return array|string
     */
    public function adv()
    {
        $where['machine_id'] = $this->machine['machine_id'];
        $where[] = ['status',"<",3];
        $where[] = ['start_date','<=', time() ];
        $where[] = ['end_date','>=', time()-86400];
        $advList = $this->getAdvertisementPushList($where,$this->data['pageNum'] ?? 0,"*");
        return $this->rQ($advList);
    }

    public function subCar()
    {
        foreach ($this->data['carList'] as $key => $value) {
            $mc = $this->getMachineChannelFind(['mc_id' => $value['mc_id']]);
            if (!$mc) return $this->r(100,'查无货道信息');
            $details = [
                "mc_id" => $mc['mc_id'],
                "channel_position" => $mc['channel_position'],
                "channel_code" => $mc['channel_code'],
                "mg_id" => $mc['mg_id'],
                "g_id" => $mc['g_id'],
                "g_name" => $mc['g_name'],
                "pic" => $mc['pic'],
                "gc_id" => $mc['gc_id'],
                "gc_name" => $mc['gc_name'],
                "cost_price" => $mc['cost_price'],
                "market_price" => $mc['market_price'],
                "retail_price" => $mc['retail_price'],
                "total_sod_price" => bcmul($mc['retail_price'],$value['quantity'],2),
                "quantity" => $value['quantity'],
                "bar_code" => $mc['bar_code'],
                "batch_number" => $mc[''],
                "manufacture_time" => $mc[''],
                "sell_by_date" => $mc[''],
            ];
        }
    }
}