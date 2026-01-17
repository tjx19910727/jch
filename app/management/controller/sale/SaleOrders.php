<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/5
 * Time: 14:24
 */

namespace app\management\controller\sale;


use app\management\controller\Common;
use think\Facade\Db;

class SaleOrders extends Common
{
    protected $validatePath = 'app\management\validate\VSaleOrders.';

    /**
     * 查询订单列表
     * @param bool supplier 供应商账号是否跳过组织选择查看所属商品订单
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $machineIds = [];
        if (!empty($postData['machine_group_id'])) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']],'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }

        $where = $this->getWhere($postData,false,['trade_no' => "like","order_type" => "in","mch_no" => "like","machine_name" => "like","machine_id" => "like","pay_type" => "in",'factory'=>'in','inventory_location'=>'in','out_status'=>'in']);
        $where['raw'] = "pay_status in ('3', '7')";
        if($this->authMchCannel()['status'] != 0){
            $orderIds = Db::name('sale_orders_details')
            ->whereIn('mc_id', $this->authMchCannel()['data']['mc_id'])
            ->field('order_id')
            ->select();

            $order_id = [];
            foreach($orderIds as $item){
                array_push($order_id,$item['order_id']);
            }
            $where[] = ['order_id','in',$order_id];
        }
        // 查看成本价查询账号是否包含当前权限
        if ($this->manager['pid'] > 0) {
            $whereArn[] = ['role_id', 'in', $this->app->authManagerRole->getAuthManagerRoleColumn(['manager_id' => $this->manager['manager_id'], 'is_del' => 2], 'role_id')];
            $whereArn['is_del'] = 2;
            $node = $this->app->authNode->getAuthNodeColumn(['url'=>'/management/sale.sale_orders/getList?get_cost_price'],'node_id');
            $authRole = $this->app->authRoleNode->getAuthRoleNodeColumn($whereArn,'node_id');
            $inter = array_intersect($node,$authRole);
            
            $whereAuth[] = ['node_id', 'in',$inter];
        }
        $whereAuth['status'] = 1;
        $data = $this->app->authNode->getAuthNodeList($whereAuth,0,'node_id,pid,name,icon,url,desc,sort,type,is_auth,is_button,status','sort asc');
        $data = obj2arr($data);

        $field = "order_id,trade_no,mch_no,total_quantity,total_price,discount_price,retail_price,out_status,order_type,pay_type,user_id,out_trade_no,pay_status,pay_time,out_time,machine_name,machine_id,discount_price,factory,inventory_location,has_hotel,refund_status,(select machine_name from machine m where m.m_id = a.m_id) machine_name,(total_price - refund_amount) total_price";
        if (!empty($data))$field .= ",cost_price";
        if (!empty($machineIds)) $where[] = ['machine_id','in',$machineIds];
        if (isset($postData['supplier']) && $postData['supplier']) unset($where['ao_id']);
        if($this->manager['level'] > 3 && !in_array($this->manager['ao_id'], [0,1] )){
            $where['ao_id'] = $this->manager['ao_id'];
        }
        return $this->app->saleOrders->getSoList($where,$pageNum,$field,"order_id desc",$postData['supplier'] ?? true);
    }

    /**
     * 查询订单详情
     * @return array|string
     * @throws \Exception
     */
    public function getDetails()
    {
        $order_id = input('order_id');
        $where['order_id'] = $order_id;
        $order = $this->app->saleOrders->getSaleOrdersFind($where,'*');
        if ($order) {
            $order['details'] = $this->app->saleOrders->getSaleOrdersDetailsList($where,0,'*','sod_id desc');
        }
        $order = $order->getData();
        return returnData($order);
    }

    /**
     * 商品交易列表
     * @param bool supplier 供应商账号是否跳过组织选择查看所属子商品订单
     * @return array|string
     */
    public function getDetailsList()
    {
        $postData = input();
        $sku = '';
        if(!empty($postData['sku'])) {
            $sku = $postData['sku'];
            unset($postData['sku']);
        }
        $where = $this->getWhere($postData,false,["g_name" => "like","sku" => 'like',"trade_no" => "like","machine_id" => 'like',"machine_name" => 'like','factory'=>'in','inventory_location'=>'in']);
        if ($sku) $where[] = ['sod.sku', 'like', '%'.$sku.'%'];
        $where['so.pay_status'] = 3;

        if($this->authMchCannel()['status'] != 0){
            $sodIds = Db::name('sale_orders_details')
            ->whereIn('mc_id', $this->authMchCannel()['data']['mc_id'])
            ->field('sod_id')
            ->select();

            $sod_id = [];
            foreach($sodIds as $item){
                array_push($sod_id,$item['sod_id']);
            }
            $where[] = ['sod.sod_id','in',$sod_id];
        }

        $field = "so.machine_id,so.machine_name,so.trade_no,so.mch_no,so.transaction_video,so.order_type,so.pay_type,so.pay_method,so.pay_time,so.out_time,so.create_time,so.out_status,so.refund_status,so.factory,so.inventory_location,
        sod.sku,sod.g_name,sod.channel_code,sod.retail_price,sod.discount_price,(sod.total_sod_price - sod.refund_amount) total_sod_price,
        (sod.success_quantity) success_quantity,(sod.fail_quantity) fail_quantity,sod.deliver_pics,(sod.quantity) quantity,sod.refund_quantity,sod.refund_amount";
        // if ($postData['supplier']) unset($where['ao_id']);
        return returnData($this->app->saleOrders->getDetailsList($where,($postData['pageNum'] ?? 0),$field,"sod_id desc",$postData['supplier'] ?? 'true'));
    }

    /**
     * 导出商品交易
     * @return array|string
     */
    public function exportGoodsList()
    {
        $postData = input();
        $m_id = 0;
        $sku = '';
        if (isset($postData['m_id']) && $postData['m_id']) {
            $m_id = $postData['m_id'];
            unset($postData['m_id']);
        }
        if(!empty($postData['sku'])) {
            $sku = $postData['sku'];
            unset($postData['sku']);
        }
        $where = $this->getWhere($postData,false,["g_name" => "like","machine_id" => 'like',"machine_name" => 'like'],'so.');
        if (isset($where['ao_id'])) {
            $where['so.ao_id'] = $where['ao_id'];
            unset($where['ao_id']);
        }
        $where['so.pay_status'] = 3;
        if ($m_id) $where['so.m_id'] = $m_id;
        if ($sku) $where[] = ['sod.sku', 'like', '%'.$sku.'%'];
        return $this->app->saleOrders->exportGoodsSo($where);
    }

    /**
     * 订单退款
     * @return array|bool|string
     */
    public function refund()
    {
        $postData = input();
        actionLog($postData,'退款数据');
        try { $this->validate($postData,$this->validatePath . 'refund');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $check = checkFrequency("refund" . $postData['order_id'],10);
        actionLog($check,"间隔日志");
        if ($check !== true) return returnState(100,$check);
        $postData['refund'] = json2arr($postData['refund']);
        return $this->app->saleOrders->refundOrder($postData);
    }

    /**
     * 下发获取交易视频
     * @return array|string
     */
    public function getTransactionVideo()
    {
        $trade_no = input('trade_no');
        if(empty(input('status'))){
            $order = $this->app->saleOrders->getFind(['trade_no' => $trade_no],'transaction_video,machine_id','',0);
            if (!$order) return returnState(100,lang("VSaleOrders.order_no_data"));
            if (!$order['transaction_video']) {
                $otherData = ['trade_no' => $trade_no];
                $result = $this->app->machine->sendToMachine(['machine_id' => $order['machine_id']],'transactionVideo',$otherData);
                return is_object($result) ? returnState(200,'正在从机器端获取视频文件，请稍做等待后下载',$result) :
                $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
            }
            return returnState(200,'查询成功',$order);
        }
        else{
            if(input('status')=='getOpenDoor'){
                $mec = $this->app->machineErrorCode->getMachineErrorCodeFind(['me_id' => input('me_id')],'transaction_video,machine_id','',0);
                if (!$mec) return returnState(100,lang("VSaleOrders.order_no_data"));
                if (!$mec['trade_no']) $this->app->machineErrorCode->updateMachineErrorCode(['trade_no' => $trade_no],['me_id' => input('me_id')]);
                if (!$mec['transaction_video']) {
                    $otherData = ['trade_no' => $trade_no];
                    $result = $this->app->machine->sendToMachine(['machine_id' => $mec['machine_id']],'transactionVideo',$otherData);
                    return is_object($result) ? returnState(200,'正在从机器端获取视频文件，请稍做等待后下载',$result) :
                    $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
                }
                return returnState(200,'查询成功',$mec);
            }
        }
    }
    /**
     * 导出订单列表信息
     * @return array|string
     * @throws \Exception
     */
    public function export()
    {
        $postData = input();
        if (isset($postData['machine_group_id']) && $postData['machine_group_id']) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']],'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }
        $where = $this->getWhere($postData,false,["order_id" => "in",'trade_no' => "like","mch_no" => "like","machine_name" => "like","machine_id" => "like"]);
        if (!empty($machineIds)) $where[] = ['machine_id', 'in', $machineIds];
        $where['ao_id'] = $this->manager['ao_id'];
        $machineIds = [];
        
        return $this->app->saleOrders->exportSo($where);
    }

    /**
     * 获取订单退款列表
     * @param bool supplier 供应商账号是否跳过组织选择查看所属商品订单
     * @return array|string
     */
    public function getRefundList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->authNodeWhere();
        if (isset($where['ao_id'])) {
            $where['sor.ao_id'] = $where['ao_id'];
            unset($where['ao_id']);
        }
        if (isset($postData['machine_group_id']) && $postData['machine_group_id']) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']],'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }
        if (isset($postData['m_id']) && $postData['m_id']) $where['sor.m_id'] = $postData['m_id'];
        if (isset($postData['trade_no']) && $postData['trade_no']) $where[] = ['sor.trade_no','like',"%" .$postData['trade_no']. "%"];
        if (isset($postData['machine_id']) && $postData['machine_id']) $where[] = ['sor.machine_id','like',"%" .$postData['machine_id']. "%"];
        if (isset($postData['refund_no']) && $postData['refund_no']) $where[] = ['sor.refund_no','like',"%" .$postData['refund_no']. "%"];
        if (isset($postData['pay_type']) && $postData['pay_type']) $where['pay_type'] = $postData['pay_type'];
//        $where = $this->getWhere($postData,false,['refund_trade_no' => "like",'machine_id' => "like",'trade_no' => "like","refund_no" => "like"]);
        if ($this->manager['pid'] > 0) {
            $mIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'm_id');
            if ($mIds) $where[] = ['sor.m_id', 'in', $mIds];
        }
        $field = "sor.*,so.pay_type";
        if (!empty($machineIds)) $where[] = ['sor.machine_id', 'in',$machineIds];
        
        if($this->authMchCannel()['status'] != 0){
            $sodIds = Db::name('sale_orders_details')
            ->whereIn('mc_id', $this->authMchCannel()['data']['mc_id'])
            ->field('sod_id')
            ->select();

            $sod_id = [];
            foreach($sodIds as $item){
                array_push($sod_id,$item['sod_id']);
            }
            $where[] = ['sod.sod_id','in',$sod_id];
        }
        // if ($postData['supplier']) unset($where['sor.ao_id']);
        return returnData($this->app->saleOrders->getSaleOrdersRefundListJoinSoSod($where,$pageNum,$field,'sor.sor_id desc'));
    }

    /**
     * 导出退款记录列表
     * @return array|string
     */
    public function exportRefund()
    {
        $postData = input();
        $where = $this->authNodeWhere();
        if (isset($postData['m_id']) && $postData['m_id']) $where['sor.m_id'] = $postData['m_id'];
        if (isset($postData['trade_no']) && $postData['trade_no']) $where[] = ['sor.trade_no','like',"%" .$postData['trade_no']. "%"];
        if (isset($postData['machine_id']) && $postData['machine_id']) $where[] = ['sor.machine_id','like',"%" .$postData['machine_id']. "%"];
        if (isset($postData['refund_no']) && $postData['refund_no']) $where[] = ['sor.refund_no','like',"%" .$postData['refund_no']. "%"];
        if (isset($postData['pay_type']) && $postData['pay_type']) $where['pay_type'] = $postData['pay_type'];
        return $this->app->saleOrders->exportRefund($where);
    }

    /**
     * 获取销售报表概况
     * @return array|string
     */
    public function getReport()
    {
        $postData = input();
        $field = "";
        $group = "";
        if (isset($postData['group'])) {
            $group = $postData['group'];
            unset($postData['group']);
        }
        $where = $this->getWhere($postData,false,["machine_id" => "like"]);

        if (!isset($postData['m_id']) || !$postData['m_id']) {
            if ($this->manager['pid'] > 0) {
                $mIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'm_id');
                if ($mIds) $where[] = ['m_id', 'in', $mIds];
            }
        }
        // if ($group) {
        //     // 日
        //     if ($group == "day") {
        //         $field = "countDate,";
        //     }
        //     // 月
        //     if ($group == "month") {
        //         $field = "DATE_FORMAT(countDate ,'%Y-%m') countDate,";
        //     }
        //     // 年
        //     if ($group == "year") {
        //         $field = "DATE_FORMAT(countDate ,'%Y') countDate,";
        //     }
        //     $group = "countDate";
        // }
        $field .= "ao_name,
        SUM(order_num) order_num,
        sum(totalRefundAmount) totalRefundAmount,
        SUM(totalRefundQuantity) totalRefundQuantity,
        SUM(totalPrice) totalPrice,
        SUM(totalDiscountPrice) totalDiscountPrice,
        SUM(totalQuantity) totalQuantity,
        SUM(giftQuantity) giftQuantity,
        SUM(coupon_used) coupon_used,
        SUM(lottery_used) lottery_used,
        SUM(lotteryAmount) lotteryAmount,
        SUM(lotteryQuantity) lotteryQuantity";
        if (isset($postData['machine_id'])) $field = "machine_id,machine_name," . $field;
        return $this->app->saleOrders->getTotalReport($where,$field,'countDate desc');
    }

    /**
     * 获取销售报表
     * @return array|string
     */
    public function getReportList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $order = "create_date desc";
        $group = "";
        if (isset($postData['group'])) {
            $group = $postData['group'];
            unset($postData['group']);
        }
        if (isset($postData['order'])) {
            $order = $postData['order'];
            unset($postData['order']);
        }
        $where = $this->getWhere($postData,true,["machine_id" => "like"]);
        if (!isset($postData['m_id']) || !$postData['m_id']) {
            if ($this->manager['pid'] > 0) {
                $mIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'm_id');
                if ($mIds) {
                    if ($where) $where .= " AND ";
                    $where .= 'm_id in (' . implode(",", $mIds) . ')';
                }
            }
        }
        return $this->app->saleOrders->getReportList($where,$pageNum,$order,$group);
    }

    /**
     * 导出销售报表
     * @return array|string
     */
    public function exportReport()
    {
        $postData = input();
        $order = "create_date desc";
        if (isset($postData['order'])) {
            $order = $postData['order'];
            unset($postData['order']);
        }
        $where = $this->getWhere($postData,false,["machine_id" => "like"]);
        if ($this->manager['pid'] > 0) {
            $mIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'm_id');
            if ($mIds) $where[] = ['m_id', 'in', $mIds];
        }
        return $this->app->saleOrders->exportReport($where,$order);
    }

    /**
     * 销售数据概况
     * @return array|\think\response\Json
     */
    public function saleDataCollect()
    {
        $postData = input();
        if (!isset($postData['create_date'])) $postData['create_date'] = date("Y-m-d",strtotime("-7 days")) . "~" . date("Y-m-d",strtotime("+1 days"));
        $where = $this->getWhere($postData,false,['machine_id' => "like","g_name" => "like"]);
        if (!isset($postData['m_id']) || !$postData['m_id']) {
            if ($this->manager['pid'] > 0) {
                $mIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'm_id');
                if ($mIds) $where[] = ['m_id', 'in', $mIds];
            }
        }
        return $this->app->saleOrders->saleDataCollect($where);

    }

    /**
     * 销售数据列表
     * @return array|\think\response\Json
     */
    public function saleDataList()
    {
        $postData = input();
        if (!isset($postData['create_date'])) $postData['create_date'] = date("Y-m-d",strtotime("-7 days")) . "~" . date("Y-m-d",strtotime("+1 days"));
        $where = $this->getWhere($postData,false,['machine_id' => "like","g_name" => "like"]);
        // 添加美驰图账号判断
        if ($this->manager['account'] != 'meichitu'){
            if (!isset($postData['m_id']) || !$postData['m_id']) {
                if ($this->manager['pid'] > 0) {
                    $mIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'm_id');
                    if ($mIds) $where[] = ['m_id', 'in', $mIds];
                }
            }
        }
        if($this->authMchCannel()['status'] != 0){
            $sodIds = Db::name('sale_orders_details')
            ->whereIn('mc_id', $this->authMchCannel()['data']['mc_id'])
            ->field('sod_id')
            ->select();

            $sod_id = [];
            foreach($sodIds as $item){
                array_push($sod_id,$item['sod_id']);
            }
            $where[] = ['sod.sod_id','in',$sod_id];
        }

        $where['so.pay_status'] = 3;
        actionLog($where,'查询条件');
        return $this->app->saleOrders->saleDataCollectList($where,$postData['pageNum'] ?? 20);
    }

    /**
     * 导出销售数据
     * @return array|\think\response\Json
     */
    public function exportSaleData()
    {
        $postData = input();
        if (!isset($postData['create_date'])) $postData['create_date'] = date("Y-m-d",strtotime("-7 days")) . "~" . date("Y-m-d",strtotime("+1 days"));
        $where = $this->getWhere($postData,false,['machine_id' => "like","g_name" => "like"]);
        if ($this->manager['pid'] > 0) {
            $mIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'm_id');
            if ($mIds) $where[] = ['m_id', 'in', $mIds];
        }
        $where['so.pay_status'] = 3;
        return $this->app->saleOrders->exportSaleDataCollect($where);
    }

    /**
     * 查询门票商品
     * @return array|\think\response\Json
     */
//    public function queryTicket()
//    {
//        $postData = input();
//        $where = $this->getWhere($postData,false,['trade_no' => "like","mobile" => "like","checkOff_code"]);
//        $field = "sod.sod_id,so.trade_no,so.machine_id,so.machine_name,so.mobile,sod.checkOff_code,sod.g_name,sod.checkOff_status,sod.checkOff_time,so.pay_time";
//        $pageNum = $postData['pageNum'] ?? 0;
//        $order = "sod.checkOff_time desc";
//        return $this->app->saleOrders->queryCheckOffList($where,$pageNum,$field,$order);
//    }

    /**
     * 核销门票商品
     * @return array|\think\response\Json
     */
//    public function checkOffTicket()
//    {
//        $postData = input();
//        if (!isset($postData['sod_id']) || !$postData['sod_id']) return returnState(100,lang("VSaleOrders.sod_id_require"));
//        if (!isset($postData['checkOff_status']) || !$postData['checkOff_status'] || in_array($postData['checkOff_status'],[2,3]))
//            return returnState(100,lang("VSaleOrders.checkOff_status_error"));
//        return $this->app->saleOrders->checkOffTicket($postData['sod_id'],$postData['checkOff_status']);
//    }

    /**
     * 查询酒店
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator
     * @throws \Exception
     */
    public function queryHotel()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,[]);
        $pageNum = $postData['pageNum'] ?? 0;
        return $this->app->saleOrders->getSaleHotelList($where,$pageNum);
    }

    /**
     * @return \app\AppFactory\Management\Sale\SaleOrdersClient|\app\AppFactory\Management\Sale\SaleOrdersClient[]|array|\think\Collection|\think\Paginator|\think\response\Json
     */
//    public function checkOffHotel()
//    {
//        $postData = input();
//        if (!isset($postData['sh_id']) || !$postData['sh_id']) return returnState(100,lang("VSaleOrders.sh_id_require"));
//        if (!isset($postData['checkOff_status']) || !$postData['checkOff_status'] || in_array($postData['checkOff_status'],[2,3]))
//            return returnState(100,lang("VSaleOrders.checkOff_status_error"));
//        return $this->app->saleOrders->checkOffHotel($postData['sh_id'],$postData['checkOff_status']);
//    }


    /**
     * 针对11月份出现的已支付订单，但是订单信息未记录完整的问题进行补录
     * @return array|string
     */
    public function fixOrdersInfo(){
        $postData = input();
        $postData['manager_id'] = $this->manager['manager_id'];
        return $this->app->saleOrders->fixOrdersInfo($postData);
    }
}