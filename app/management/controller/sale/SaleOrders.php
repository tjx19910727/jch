<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/5
 * Time: 14:24
 */

namespace app\management\controller\sale;


use app\management\controller\Common;
use think\facade\Db;

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
        $channelCode = trim((string)($postData['channel_code'] ?? ''));
        $supplier = $postData['supplier'] ?? null;
        unset($postData['channel_code']);
        unset($postData['supplier']);
        if (!empty($postData['machine_group_id'])) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']],'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }

        $where = $this->getWhere($postData,false,['trade_no' => "like","order_type" => "in","mch_no" => "like","machine_name" => "like","machine_id" => "like","pay_type" => "in","pay_channel" => "in",'factory'=>'in','inventory_location'=>'in','out_status'=>'in']);
        $where['raw'] = "pay_status in ('3', '7')";
        $authMch = $this->authMchCannel();
        if($authMch['status'] != 0){
            $orderIds = Db::name('sale_orders_details')
                ->whereIn('mc_id', $authMch['data']['mc_id'])
                ->column('order_id');
            $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
            $where[] = ['order_id', 'in', $orderIds ?: [0]];
        }
        if ($channelCode !== '') {
            $orderIds = Db::name('sale_orders_details')
                ->where('channel_code', 'like', '%' . $channelCode . '%')
                ->column('order_id');
            $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
            $where[] = ['order_id', 'in', $orderIds ?: [0]];
        }
        $hasCostPriceAuth = $this->hasCostPriceAuth();

        $costPriceField = $hasCostPriceAuth ? "cost_price" : "0 cost_price";
        $field = "order_id,trade_no,mch_no,total_quantity,total_price,total_points,discount_price,retail_price,out_status,http_out_status,order_type,pay_type,pay_method,pay_channel,pay_channel_name,user_id,out_trade_no,pay_status,pay_time,out_time,machine_name,a.m_id,a.machine_id,a.machine_level,
        factory,inventory_location,has_hotel,refund_status,(total_price - refund_amount) total_price, (total_cost_points - refund_cost_points) total_cost_points, pay_code, mobile,{$costPriceField}";
        if (!empty($machineIds)) $where[] = ['machine_id','in',$machineIds];
        if ($supplier) unset($where['ao_id']);
        if($this->manager['level'] > 3 && !in_array($this->manager['ao_id'], [0,1] )){
            $where['ao_id'] = $this->manager['ao_id'];
        }
        return $this->app->saleOrders->getSoList($where,$pageNum,$field,"order_id desc",$supplier ?? true);
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
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        $sku = '';
        $g_name = '';
        if(isset($postData['sku']) && !empty($postData['sku'])) {
            $sku = $postData['sku'];
            unset($postData['sku']);
        }
        if(isset($postData['g_name']) && !empty($postData['g_name'])) {
            $g_name = $postData['g_name'];
            unset($postData['g_name']);
        }
        $where = $this->getWhere($postData,false,["trade_no" => "like","machine_id" => 'like',"machine_name" => 'like','factory'=>'in','inventory_location'=>'in']);
        if ($sku) $where[] = ['sod.sku', 'like', '%'.$sku.'%'];
        if ($g_name) $where[] = ['sod.g_name', 'like', '%'.$g_name.'%'];
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
        if (isset($postData['supplier']) && $postData['supplier']) unset($where['ao_id']);
        if($this->manager['level'] > 3 && !in_array($this->manager['ao_id'], [0,1] )){
            $where['so.ao_id'] = $this->manager['ao_id'];
        }

        $costPriceField = $hasCostPriceAuth ? "sod.cost_price" : "0 cost_price";
        $field = "so.machine_id,so.machine_name,so.trade_no,so.mch_no,so.transaction_video,so.order_type,so.pay_type,so.pay_method,so.pay_channel,so.pay_channel_name,so.pay_time,so.out_time,so.create_time,so.out_status,so.refund_status,so.factory,so.inventory_location,
        sod.sku,sod.g_name,sod.channel_code,sod.retail_price,sod.discount_price,(sod.total_sod_price - sod.refund_amount) total_sod_price,(sod.total_sod_points - sod.refund_points) total_sod_points,(sod.total_sod_cost_points - sod.refund_cost_points) total_sod_cost_points,
        (sod.success_quantity) success_quantity,(sod.fail_quantity) fail_quantity,sod.deliver_pics,(sod.quantity) quantity,sod.refund_quantity,sod.refund_amount,(SELECT organization_name FROM auth_organization ao WHERE ao.ao_id = sod.sod_ao_id) organization_name,{$costPriceField}";
        // if ($postData['supplier']) unset($where['ao_id']);                                                                                                                                                                                                                                                                                                                                                                                                           
        // $where['raw'] = 'so.ao_id = '. $this->manager['ao_id'].' or sod.sod_ao_id ='.$this->manager['ao_id'];
        return returnData($this->app->saleOrders->getDetailsList($where,($postData['pageNum'] ?? 0),$field,"sod_id desc",$postData['supplier'] ?? 'true'));
    }

    /**
     * 导出商品交易
     * @return array|string
     */
    public function exportGoodsList()
    {
        $postData = input();
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        $mIds = [];
        $machineIds = [];
        $sku = '';
        $g_name = '';
        if (isset($postData['m_id']) && $postData['m_id']) {
            $mIds = $this->parseExportGoodsListIds($postData['m_id']);
            unset($postData['m_id']);
        }
        if (isset($postData['machine_id']) && $postData['machine_id']) {
            $machineIds = $this->parseExportGoodsListIds($postData['machine_id']);
            if (count($machineIds) > 1) unset($postData['machine_id']);
        }
        if(!empty($postData['sku'])) {
            $sku = $postData['sku'];
            unset($postData['sku']);
        }
        if(!empty($postData['g_name'])) {
            $g_name = $postData['g_name'];
            unset($postData['g_name']);
        }
        $where = $this->getWhere($postData,false,["machine_id" => 'like',"machine_name" => 'like'],'so.');
        $where = $this->formatAoIdWhereWithPrefix($where, 'so.');
        // if (isset($where['ao_id'])) {
        //     $where['so.ao_id'] = $where['ao_id'];
        //     unset($where['ao_id']);
        // }
        $where['so.pay_status'] = 3;
        if ($mIds) $where[] = ['so.m_id', 'in', $mIds];
        if (count($machineIds) > 1) $where[] = ['so.machine_id', 'in', $machineIds];
        if ($sku) $where[] = ['sod.sku', 'like', '%'.$sku.'%'];
        if ($g_name) $where[] = ['sod.g_name', 'like', '%'.$g_name.'%'];
        return $this->app->saleOrders->exportGoodsSo($where, $hasCostPriceAuth);
    }

    protected function parseExportGoodsListIds($value)
    {
        if (is_array($value)) {
            $ids = $value;
        } else {
            $ids = explode(',', (string)$value);
        }
        $ids = array_map(function ($id) {
            return trim((string)$id);
        }, $ids);
        $ids = array_filter($ids, function ($id) {
            return $id !== '';
        });
        return array_values(array_unique($ids));
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
     * 线下退款（人工打款，不调支付平台）
     * @return array|bool|string
     */
    public function offlineRefund()
    {
        $postData = input();
        actionLog($postData, '线下退款数据');
        try {
            $this->validate($postData, $this->validatePath . 'offlineRefund');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $check = checkFrequency('offline_refund' . $postData['order_id'], 10);
        if ($check !== true) {
            return returnState(100, $check);
        }
        $postData['refund'] = json2arr($postData['refund']);
        return $this->app->saleOrders->offlineRefundOrder($postData);
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
            if(input('status')=='remoteOutGoods'){
                $machine_id = input('machine_id');
                $tmp = explode('_',$trade_no);
                $real_sod_id = $tmp[count($tmp)-1];
                $sod = $this->app->saleOrders->getSaleOrdersDetailsFind(['sod_id' => $real_sod_id], 'remote_out_goods_video');
                if (!$sod) return returnState(100,lang("VSaleOrders.order_no_data"));
                if (!$sod['remote_out_goods_video']) {
                    $otherData = ['trade_no' => $trade_no];
                    $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], 'transactionVideo',$otherData);
                    return is_object($result) ? returnState(200,'正在从机器端获取视频文件，请稍做等待后下载',$result) :
                    $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
                }
                return returnState(200,'查询成功',$sod);
            }
        }
    }

    /**
     * 设置子订单远程退货状态
     * 接口参数：sod_id int, status int (0-未退货，1-已退货)
     * @return array|string
     */
    public function setRemoteRefundStatus()
    {
        $postData = input();
        $machine_id = input("machine_id");
        $sod_id = intval($postData['sod_id'] ?? 0);
        $remote_refund_status = intval($postData['remote_refund_status'] ?? 0);
        if (!$sod_id) return returnState(100, 'sod_id is required');

        $sale_orders_details = $this->app->saleOrders->getSaleOrdersDetailsFind(['sod_id' => $sod_id], 'remote_refund_status');
        if (!$sale_orders_details) return returnState(100, '订单详情不存在');

        $this->app->saleOrders->updateSaleOrdersDetails(['remote_refund_status' => $remote_refund_status, 'remote_refund_audit_manager' => $this->manager['manager_id']], ['sod_id' => $sod_id]);
        if($remote_refund_status == 2){
            $this->app->machine->sendToMachine(['machine_id' => $machine_id], "recycGoods", ['sod_id' => $sod_id]);
        }
        return returnState(200, 'success', ['remote_refund_status' => $remote_refund_status]);
    }
    /**
     * 导出订单列表信息
     * @return array|string
     * @throws \Exception
     */
    public function export()
    {
        $postData = input();
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        $machineIds = [];
        $channelCode = trim((string)($postData['channel_code'] ?? ''));
        $supplier = $postData['supplier'] ?? null;
        unset($postData['channel_code']);
        unset($postData['supplier']);
        if (isset($postData['machine_group_id']) && $postData['machine_group_id']) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']],'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }
        $where = $this->getWhere($postData,false,["order_id" => "in",'trade_no' => "like","order_type" => "in","mch_no" => "like","machine_name" => "like","machine_id" => "like","pay_type" => "in","pay_channel" => "in",'factory'=>'in','inventory_location'=>'in','out_status'=>'in']);
        $authMch = $this->authMchCannel();
        if ($authMch['status'] != 0) {
            $orderIds = Db::name('sale_orders_details')
                ->whereIn('mc_id', $authMch['data']['mc_id'])
                ->column('order_id');
            $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
            $where[] = ['order_id', 'in', $orderIds ?: [0]];
        }
        if (!empty($machineIds)) $where[] = ['machine_id', 'in', $machineIds];
        if ($channelCode !== '') {
            $orderIds = Db::name('sale_orders_details')
                ->where('channel_code', 'like', '%' . $channelCode . '%')
                ->column('order_id');
            $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
            $where[] = ['order_id', 'in', $orderIds ?: [0]];
        }
        if ($supplier) unset($where['ao_id']);
        if($this->manager['level'] > 3 && !in_array($this->manager['ao_id'], [0,1] )){
            $where['ao_id'] = $this->manager['ao_id'];
        }
        
        return $this->app->saleOrders->exportSo($where, $hasCostPriceAuth);
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
        $update_time = '';
        if(isset($postData['create_time']) && !empty($postData['create_time'])) $update_time = $postData['create_time'];
        $timeWhere = $this->getWhere(['sor.update_time' => $update_time]);
        $where = $this->authNodeWhere();
        $where = $timeWhere + $where;
        $where = $this->formatAoIdWhereWithPrefix($where, 'sor.');
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
        if (isset($postData['pay_channel']) && $postData['pay_channel']) $where['so.pay_channel'] = $postData['pay_channel'];
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
        if (isset($postData['pay_channel']) && $postData['pay_channel']) $where['so.pay_channel'] = $postData['pay_channel'];
        $where = $this->formatAoIdWhereWithPrefix($where, 'sor.');
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
        $machine_group_id = '';
        $mIds = [];

        if(isset($postData['m_id']) && !empty($postData['m_id'])){
            if(strstr($postData['m_id'],',')){
                $mIds = explode(',',$postData['m_id']);
            }else{
                $mIds = [$postData['m_id']];
            }
            unset($postData['m_id']);
        }
        if (isset($postData['group'])) {
            $group = $postData['group'];
            unset($postData['group']);
        }
        if(isset($postData['machine_group_id']) && $postData['machine_group_id']){
            $machine_group_id = $postData['machine_group_id'];
            unset($postData['machine_group_id']);
        }
        $where = $this->getWhere($postData,false,["machine_id" => "like"]);
        if(!empty($mIds)) $where[] = ['m_id', 'in', $mIds];
        if($machine_group_id){
            $mIds_arr = $this->app->machine->getMachineGroupMGList(['mg_id' => $machine_group_id],0,'m_id')->toArray();
            $mIds = array_column($mIds_arr,'m_id');
            $where[] = ['m_id', 'in', $mIds];
        }
        
        if (!isset($postData['m_id']) || !$postData['m_id'] || !$machine_group_id) {
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
    SUM(totalPrice - totalRefundAmount) totalSalePrice,
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
        $machine_group_id = '';
        $mIds = [];

        if(isset($postData['m_id']) && !empty($postData['m_id'])){
            if(strstr($postData['m_id'],',')){
                $mIds = explode(',',$postData['m_id']);
            }else{
                $mIds = [$postData['m_id']];
            }
            unset($postData['m_id']);
        }
        if (isset($postData['group'])) {
            $group = $postData['group'];
            unset($postData['group']);
        }
        if (isset($postData['order'])) {
            $order = $postData['order'];
            unset($postData['order']);
        }
        if(isset($postData['machine_group_id']) && $postData['machine_group_id']){
            $machine_group_id = $postData['machine_group_id'];
            unset($postData['machine_group_id']);
        }
        $where = $this->getWhere($postData, false,["machine_id" => "like"]);
        if(!empty($mIds)) $where[] = ['m_id', 'in', $mIds];
        if($machine_group_id){
            $mIds_arr = $this->app->machine->getMachineGroupMGList(['mg_id' => $machine_group_id],0,'m_id')->toArray();
            $mIds = array_column($mIds_arr,'m_id');
            $where[] = ['m_id', 'in', $mIds];
        }
        if (!isset($postData['m_id']) || !$postData['m_id'] || !$machine_group_id) {
            if ($this->manager['pid'] > 0) {
                $mIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'm_id');
                if ($mIds) $where[] = ['m_id', 'in', $mIds];
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
        $pageNum = $postData['pageNum'] ?? 0;
        $order = "create_date desc";
        $group = "";
        $machine_group_id = '';
        $mIds = [];

        if(isset($postData['m_id']) && !empty($postData['m_id'])){
            if(strstr($postData['m_id'],',')){
                $mIds = explode(',',$postData['m_id']);
            }else{
                $mIds = [$postData['m_id']];
            }
            unset($postData['m_id']);
        }
        if (isset($postData['group'])) {
            $group = $postData['group'];
            unset($postData['group']);
        }
        if (isset($postData['order'])) {
            $order = $postData['order'];
            unset($postData['order']);
        }
        if(isset($postData['machine_group_id']) && $postData['machine_group_id']){
            $machine_group_id = $postData['machine_group_id'];
            unset($postData['machine_group_id']);
        }
        $where = $this->getWhere($postData, false,["machine_id" => "like"]);
        if(!empty($mIds)) $where[] = ['m_id', 'in', $mIds];
        if($machine_group_id){
            $mIds_arr = $this->app->machine->getMachineGroupMGList(['mg_id' => $machine_group_id],0,'m_id')->toArray();
            $mIds = array_column($mIds_arr,'m_id');
            $where[] = ['m_id', 'in', $mIds];
        }
        if (!isset($postData['m_id']) || !$postData['m_id'] || !$machine_group_id) {
            if ($this->manager['pid'] > 0) {
                $mIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'm_id');
                if ($mIds) $where[] = ['m_id', 'in', $mIds];
            }
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
        // $where['sod.ao_id'] = $this->manager['ao_id'];
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
        $where = $this->formatAoIdWhereWithPrefix($where, 'so.');
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
        //$where['raw'] = 'so.ao_id = '. $this->manager['ao_id'].' or sod.sod_ao_id ='.$this->manager['ao_id'];
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
        $where = $this->formatAoIdWhereWithPrefix($where, 'so.');
        if ($this->manager['pid'] > 0) {
            $mIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'm_id');
            if ($mIds) $where[] = ['m_id', 'in', $mIds];
        }
        $where['so.pay_status'] = 3;
        $where['raw'] = 'so.ao_id = '. $this->manager['ao_id'].' or sod.sod_ao_id ='.$this->manager['ao_id'];
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

    /**
     * 历史订单分类回填
     * 参数：
     * batch_size,max_batches,start_order_id,end_order_id,only_unclassified,dry_run
     * @return array|string
     */
    public function backfillPayChannel()
    {
        $postData = input();
        return $this->app->saleOrders->backfillPayChannelHistory($postData);
    }

    /**
     * 异常订单处理列表
     * 列表查询条件与订单列表一致，增加异常处理状态字段：1.已处理，2.未处理
     * @param bool supplier 供应商账号是否跳过组织选择查看所属商品订单
     * @return mixed
     */
    public function getExceptionList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;

        $isProcessed = $postData['is_processed'] ?? '';
        unset($postData['is_processed']);

        $allowOutStatus = ['2', '5', '6'];
        if (isset($postData['out_status']) && $postData['out_status'] !== '') {
            $outStatusList = array_values(array_filter(array_map('trim', explode(',', $postData['out_status']))));
            $invalidOutStatus = array_diff($outStatusList, $allowOutStatus);
            if ($invalidOutStatus) return returnState(100, 'out_status 仅支持 2,5,6');
            if (!$outStatusList) return returnState(100, 'out_status 仅支持 2,5,6');
            $postData['out_status'] = implode(',', $outStatusList);
        } else {
            $postData['out_status'] = implode(',', $allowOutStatus);
        }

        $machineIds = [];
        if (!empty($postData['machine_group_id'])) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']], 'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }

        $where = $this->getWhere($postData, false, ['trade_no' => "like", "order_type" => "in", "mch_no" => "like", "machine_name" => "like", "machine_id" => "like", "pay_type" => "in", "pay_channel" => "in", 'factory' => 'in', 'inventory_location' => 'in', 'out_status' => 'in'], 'a.');
        $where['raw'] = "a.pay_status in ('3', '7')";
        if ($isProcessed == 1) {
            $where['raw'] .= " AND se.status = 1";
        }
        if ($isProcessed == 2) {
            $where['raw'] .= " AND (se.status = 2 OR se.status IS NULL)";
        }
        $authMch = $this->authMchCannel();
        if (($authMch['status'] ?? 0) != 0) {
            $mcIds = $authMch['data']['mc_id'] ?? [];
            if (empty($mcIds)) return $this->app->machine->rNoData();
            $orderIds = Db::name('sale_orders_details')
                ->whereIn('mc_id', $mcIds)
                ->field('order_id')
                ->select();

            $order_id = [];
            foreach ($orderIds as $item) {
                array_push($order_id, $item['order_id']);
            }
            $where[] = ['a.order_id', 'in', $order_id];
        }
        $hasCostPriceAuth = $this->hasCostPriceAuth();

        $costPriceField = $hasCostPriceAuth ? "a.cost_price" : "0 cost_price";
        $field = "a.order_id,a.trade_no,a.mch_no,a.total_quantity,a.total_price,a.total_points,a.retail_price,a.out_status,a.order_type,a.pay_type,a.pay_method,a.pay_channel,a.pay_channel_name,a.user_id,a.out_trade_no,a.pay_status,a.pay_time,a.out_time,a.machine_name,a.machine_id,a.discount_price,a.factory,a.inventory_location,a.has_hotel,a.refund_status,(a.total_price - a.refund_amount) total_price,(a.total_cost_points - a.refund_cost_points) total_cost_points,a.pay_code,a.mobile,se.status exception_status,se.remark exception_remark,se.manager_id exception_manager_id,se.create_time exception_create_time,am.account manager_account,am.nickname manager_nickname,{$costPriceField}";
        if (!empty($machineIds)) $where[] = ['a.machine_id', 'in', $machineIds];
        if (isset($postData['supplier']) && $postData['supplier']) unset($where['a.ao_id']);
        if ($this->manager['level'] > 3 && !in_array($this->manager['ao_id'], [0, 1])) {
            $where['a.ao_id'] = $this->manager['ao_id'];
        }
        return $this->app->saleOrders->getExceptionSoList($where, $pageNum, $field, 'a.pay_time desc', $postData['supplier'] ?? true);
    }

    /**
     * 导出异常订单列表
     * @param bool supplier 供应商账号是否跳过组织选择查看所属商品订单
     * @return array|string
     */
    public function exportException()
    {
        $postData = input();

        $isProcessed = $postData['is_processed'] ?? '';
        unset($postData['is_processed']);

        $allowOutStatus = ['2', '5', '6'];
        if (isset($postData['out_status']) && $postData['out_status'] !== '') {
            $outStatusList = array_values(array_filter(array_map('trim', explode(',', $postData['out_status']))));
            $invalidOutStatus = array_diff($outStatusList, $allowOutStatus);
            if ($invalidOutStatus) return returnState(100, 'out_status 仅支持 2,5,6');
            if (!$outStatusList) return returnState(100, 'out_status 仅支持 2,5,6');
            $postData['out_status'] = implode(',', $outStatusList);
        } else {
            $postData['out_status'] = implode(',', $allowOutStatus);
        }

        $machineIds = [];
        if (!empty($postData['machine_group_id'])) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']], 'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }

        $where = $this->getWhere($postData, false, ['trade_no' => "like", "order_type" => "in", "mch_no" => "like", "machine_name" => "like", "machine_id" => "like", "pay_type" => "in", "pay_channel" => "in", 'factory' => 'in', 'inventory_location' => 'in', 'out_status' => 'in'], 'a.');
        $where['raw'] = "a.pay_status in ('3', '7')";
        if ($isProcessed == 1) {
            $where['raw'] .= " AND se.status = 1";
        }
        if ($isProcessed == 2) {
            $where['raw'] .= " AND (se.status = 2 OR se.status IS NULL)";
        }
        $authMch = $this->authMchCannel();
        if (($authMch['status'] ?? 0) != 0) {
            $mcIds = $authMch['data']['mc_id'] ?? [];
            if (empty($mcIds)) return $this->app->machine->rNoData();
            $orderIds = Db::name('sale_orders_details')
                ->whereIn('mc_id', $mcIds)
                ->field('order_id')
                ->select();

            $order_id = [];
            foreach ($orderIds as $item) {
                array_push($order_id, $item['order_id']);
            }
            $where[] = ['a.order_id', 'in', $order_id];
        }
        if (!empty($machineIds)) $where[] = ['a.machine_id', 'in', $machineIds];
        if (isset($postData['supplier']) && $postData['supplier']) unset($where['a.ao_id']);
        if ($this->manager['level'] > 3 && !in_array($this->manager['ao_id'], [0, 1])) {
            $where['a.ao_id'] = $this->manager['ao_id'];
        }

        return $this->app->saleOrders->exportExceptionSo($where, $postData['supplier'] ?? true);
    }

    /**
     * 异常订单处理
     * @return array|string
     */
    public function exceptionHandle()
    {
        $postData = input();
        return $this->app->saleOrders->exceptionHandle($postData);
    }

    /**
     * 远程回收订单详情
     */
    public function remoteRecycleSodDetail()
    {
        return $this->app->saleOrders->getRemoteRecycleSodDetail(input());
    }

    
    /**
     * 支付方式统计
     * 统计各支付方式实收总额（total_price - refund_amount）
     * where条件与订单列表接口一致
     * @return array|string
     */
    public function payTypeStatistics()
    {
        $postData = input();
        $machineIds = [];
        $channelCode = trim((string)($postData['channel_code'] ?? ''));
        $supplier = $postData['supplier'] ?? null;
        unset($postData['channel_code']);
        unset($postData['supplier']);
        if (!empty($postData['machine_group_id'])) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']], 'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }

        $where = $this->getWhere($postData, false, ['trade_no' => "like", "order_type" => "in", "mch_no" => "like", "machine_name" => "like", "machine_id" => "like", "pay_type" => "in", "pay_channel" => "in", 'factory' => 'in', 'inventory_location' => 'in', 'out_status' => 'in']);
        $where['raw'] = "pay_status in ('3', '7')";
        $authMch = $this->authMchCannel();
        if ($authMch['status'] != 0) {
            $orderIds = Db::name('sale_orders_details')
                ->whereIn('mc_id', $authMch['data']['mc_id'])
                ->column('order_id');
            $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
            $where[] = ['order_id', 'in', $orderIds ?: [0]];
        }
        if ($channelCode !== '') {
            $orderIds = Db::name('sale_orders_details')
                ->where('channel_code', 'like', '%' . $channelCode . '%')
                ->column('order_id');
            $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
            $where[] = ['order_id', 'in', $orderIds ?: [0]];
        }
        if (!empty($machineIds)) $where[] = ['machine_id', 'in', $machineIds];
        if ($supplier) unset($where['ao_id']);
        if ($this->manager['level'] > 3 && !in_array($this->manager['ao_id'], [0, 1])) {
            $where['ao_id'] = $this->manager['ao_id'];
        }

        $raw = $where['raw'] ?? '';
        unset($where['raw']);
        $query = Db::name('sale_orders')->where($where);
        if ($raw) $query->whereRaw($raw);

        $list = $query->field('pay_type, SUM(GREATEST(total_price - refund_amount, 0)) total_amount')
            ->group('pay_type')
            ->select()
            ->toArray();

        $amounts = [];
        foreach ($list as $item) {
            $amounts[$item['pay_type']] = round($item['total_amount'], 2);
        }

        $result = [
            'wechat_scan'       => $amounts[11] ?? 0,    // 微信扫码支付
            'wechat_reverse'    => $amounts[12] ?? 0,    // 微信反扫支付
            'alipay_scan'       => $amounts[21] ?? 0,    // 支付宝扫码支付
            'alipay_reverse'    => $amounts[22] ?? 0,    // 支付宝反扫支付
            'unionpay_intl'     => $amounts[33] ?? 0,    // 国际银联
            'octopus'           => ($amounts[10] ?? 0) + ($amounts[34] ?? 0), // 八达通(10+34)
            'unionpay_card'     => $amounts[35] ?? 0,    // 银联卡
            'cash'              => $amounts[36] ?? 0,    // 纸币
            'coin'              => $amounts[37] ?? 0,    // 硬币
            'points'            => $amounts[9] ?? 0,     // 积分(商场积分支付)
            'balance'           => $amounts[20] ?? 0,    // 余额支付
            'jd_pay'            => $amounts[4] ?? 0,     // 京东支付
            'member_pay'        => $amounts[5] ?? 0,     // 会员支付
            'licheng_online'    => $amounts[6] ?? 0,     // 丽呈线上支付
            'robot_online'      => $amounts[7] ?? 0,     // 机器人线上支付
        ];

        return returnData($result);
    }

    /**
     * 手动扣库存
     * 传入sod_id，校验条件后手动扣减货道库存并更新子订单success_quantity=1
     * @return array|string
     */
    public function stockDeduction()
    {
        return $this->app->saleOrders->manualDeductStock(input());
    }

}
