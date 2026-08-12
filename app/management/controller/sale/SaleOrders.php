<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/5
 * Time: 14:24
 */

namespace app\management\controller\sale;


use app\management\controller\Common;
use app\AppFactory\Kernel\Model\SaleOrders\SaleOrdersVideoModel;
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
        // 首页跳转使用 create_date，统一转换为订单列表的支付时间条件。
        if (!empty($postData['create_date']) && empty($postData['pay_time'])) {
            $postData['pay_time'] = $postData['create_date'];
        }
        unset($postData['create_date']);
        $where = $this->getWhere($postData,false,['trade_no' => "like","order_type" => "in","mch_no" => "like","machine_name" => "like","machine_id" => "like","pay_type" => "in",'factory'=>'in','inventory_location'=>'in','out_status'=>'in','run_mode'=>'in']);
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
        $field = "order_id,trade_no,mch_no,total_quantity,total_price,total_points,discount_price,retail_price,out_status,http_out_status,order_type,pay_type,pay_method,user_id,out_trade_no,pay_status,pay_time,out_time,machine_name,a.m_id,a.machine_id,a.machine_level,a.run_mode,(CASE a.run_mode WHEN 2 THEN '测试模式' ELSE '生产模式' END) run_mode_desc,
        factory,inventory_location,has_hotel,refund_status,(total_price - refund_amount) total_price, (total_cost_points - refund_cost_points) total_cost_points, pay_code, mobile,receipt,{$costPriceField}";
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
        $where = $this->getWhere($postData,false,["trade_no" => "like","machine_id" => 'like',"machine_name" => 'like','factory'=>'in','inventory_location'=>'in','run_mode'=>'in']);
        foreach ($where as $whereKey => $whereItem) {
            if (is_array($whereItem) && isset($whereItem[0]) && $whereItem[0] === 'run_mode') {
                $where[$whereKey][0] = 'so.run_mode';
            }
        }
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
        $field = "so.machine_id,so.machine_name,so.trade_no,so.mch_no,so.transaction_video,so.order_type,so.pay_type,so.pay_method,so.pay_time,so.out_time,so.create_time,so.out_status,so.refund_status,so.factory,so.inventory_location,so.run_mode,(CASE so.run_mode WHEN 2 THEN '测试模式' ELSE '生产模式' END) run_mode_desc,
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
        $gIds = [];
        $machineIds = [];
        $sku = '';
        $g_name = '';
        if (isset($postData['m_id']) && $postData['m_id']) {
            $mIds = $this->parseExportGoodsListIds($postData['m_id']);
            unset($postData['m_id']);
        }
        if (isset($postData['g_id']) && $postData['g_id']) {
            $gIds = $this->parseExportGoodsListIds($postData['g_id']);
            unset($postData['g_id']);
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
        if ($gIds) $where[] = ['sod.g_id', 'in', $gIds];
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
            $order = $this->app->saleOrders->getFind(['trade_no' => $trade_no],'order_id,trade_no,transaction_video,machine_id','',0);
            if (!$order) return returnState(100,lang("VSaleOrders.order_no_data"));
            $videoResult = $this->app->saleOrders->getSaleOrdersVideoResult(SaleOrdersVideoModel::TYPE_SALE_ORDER, $order['order_id']);
            if (!$videoResult['has_records'] && !$order['transaction_video']) {
                $otherData = ['trade_no' => $trade_no];
                $result = $this->app->machine->sendToMachine(['machine_id' => $order['machine_id']],'transactionVideo',$otherData);
                return is_object($result) ? returnState(200,'正在从机器端获取视频文件，请稍做等待后下载',$result) :
                $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
            }
            if ($videoResult['has_records'] && !$videoResult['complete']) {
                return returnState(200, '视频文件正在上传，请稍后重试');
            }
            $order['transaction_videos'] = $videoResult['has_records']
                ? $videoResult['videos']
                : $this->app->saleOrders->getLegacyTransactionVideos($order['trade_no'], $order['transaction_video']);
            unset($order['order_id'], $order['trade_no']);
            return returnState(200,'查询成功',$order);
        }
        else{
            if(input('status')=='getOpenDoor'){
                $mec = $this->app->machineErrorCode->getMachineErrorCodeFind(['me_id' => input('me_id')],'transaction_video,machine_id,errorCode','',0);
                if (!$mec) return returnState(100,lang("VSaleOrders.order_no_data"));
                if (!$mec['trade_no']) $this->app->machineErrorCode->updateMachineErrorCode(['trade_no' => $trade_no],['me_id' => input('me_id')]);
                if (!$mec['transaction_video']) {
                    $doorType = ['1200000'=>1,'1200010'=>2,'1200020'=>3]; // 默认为1机台，2远程，3钥匙
                    $otherData = ['trade_no' => $trade_no,'door_type' => $doorType[$mec['errorCode']] ?? 1];//钥匙， 2机台  3远程
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
                $sod = $this->app->saleOrders->getSaleOrdersDetailsFind(['sod_id' => $real_sod_id], 'sod_id,remote_out_goods_video');
                if (!$sod) return returnState(100,lang("VSaleOrders.order_no_data"));
                $videoResult = $this->app->saleOrders->getSaleOrdersVideoResult(SaleOrdersVideoModel::TYPE_REMOTE_OUT_GOODS, $sod['sod_id']);
                if (!$videoResult['has_records'] && !$sod['remote_out_goods_video']) {
                    $otherData = ['trade_no' => $trade_no];
                    $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], 'transactionVideo',$otherData);
                    return is_object($result) ? returnState(200,'正在从机器端获取视频文件，请稍做等待后下载',$result) :
                    $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
                }
                if ($videoResult['has_records'] && !$videoResult['complete']) {
                    return returnState(200, '视频文件正在上传，请稍后重试');
                }
                $sod['transaction_videos'] = $videoResult['has_records']
                    ? $videoResult['videos']
                    : $this->app->saleOrders->getLegacyTransactionVideos($trade_no, $sod['remote_out_goods_video']);
                unset($sod['sod_id']);
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
        $where = $this->getWhere($postData,false,["order_id" => "in",'trade_no' => "like","order_type" => "in","mch_no" => "like","machine_name" => "like","machine_id" => "like","pay_type" => "in",'factory'=>'in','inventory_location'=>'in','out_status'=>'in','run_mode'=>'in']);
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
        $behaviorWhere = $this->formatBehaviorTrackingWhere($postData);
        $postData['pay_time'] = $postData['create_date'];
        unset($postData['create_date']);
        $where = $this->getWhere($postData,false,['machine_id' => "like","g_name" => "like"]);
        if (!isset($postData['m_id']) || !$postData['m_id']) {
            if ($this->manager['pid'] > 0) {
                $mIds = $this->app->authManagerMachine->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], 'm_id');
                if ($mIds) $where[] = ['m_id', 'in', $mIds];
            }
        }
        // $where['sod.ao_id'] = $this->manager['ao_id'];
        return $this->app->saleOrders->saleDataCollect($where,$behaviorWhere);

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
        $behaviorWhere = $this->formatBehaviorTrackingWhere($postData);
        return $this->app->saleOrders->saleDataCollectList($where,$postData['pageNum'] ?? 20,$behaviorWhere);
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
        //$where['raw'] = 'so.ao_id = '. $this->manager['ao_id'].' or sod.sod_ao_id ='.$this->manager['ao_id'];
        $behaviorWhere = $this->formatBehaviorTrackingWhere($postData);
        return $this->app->saleOrders->exportSaleDataCollect($where,$behaviorWhere);
    }

    /**
     * 将销售统计条件映射为商品行为埋点查询条件。
     */
    protected function formatBehaviorTrackingWhere($postData)
    {
        $fieldMap = [
            'm_id' => 'm_id',
            'machine_id' => 'machine_id',
            'g_id' => 'goods_id',
            'is_online' => 'is_online',
            'create_date' => 'device_created_at',
        ];
        $behaviorPostData = [];
        foreach ($fieldMap as $sourceField => $targetField) {
            if (array_key_exists($sourceField, $postData)) {
                $behaviorPostData[$targetField] = $postData[$sourceField];
            }
        }

        $where = $this->getWhere($behaviorPostData, false, ['machine_id' => 'like'], 'gbt.');
        return $this->formatAoIdWhereWithPrefix($where, 'm.');
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

        $where = $this->getWhere($postData, false, ['trade_no' => "like", "order_type" => "in", "mch_no" => "like", "machine_name" => "like", "machine_id" => "like", "pay_type" => "in", 'factory' => 'in', 'inventory_location' => 'in', 'out_status' => 'in'], 'a.');
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
        $field = "a.order_id,a.trade_no,a.mch_no,a.total_quantity,a.total_price,a.total_points,a.retail_price,a.out_status,a.order_type,a.pay_type,a.pay_method,a.user_id,a.out_trade_no,a.pay_status,a.pay_time,a.out_time,a.machine_name,a.machine_id,a.discount_price,a.factory,a.inventory_location,a.has_hotel,a.refund_status,(a.total_price - a.refund_amount) total_price,(a.total_cost_points - a.refund_cost_points) total_cost_points,a.pay_code,a.mobile,se.status exception_status,se.remark exception_remark,se.manager_id exception_manager_id,se.create_time exception_create_time,am.account manager_account,am.nickname manager_nickname,{$costPriceField}";
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

        $where = $this->getWhere($postData, false, ['trade_no' => "like", "order_type" => "in", "mch_no" => "like", "machine_name" => "like", "machine_id" => "like", "pay_type" => "in", 'factory' => 'in', 'inventory_location' => 'in', 'out_status' => 'in'], 'a.');
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
     * 获取远程出货步骤详情列表（按step正序）
     */
    public function remoteOutGoodsStepsDetail()
    {
        return $this->app->saleOrders->getRemoteOutGoodsStepsDetail(input());
    }

    
    /**
     * 支付方式统计
     * 订单主表统计支付总额、退款总额与订单数，明细表统计成本与数量。
     * where条件与订单列表接口一致。
     * @return array|string
     */
    public function payTypeStatistics()
    {
        $postData = input();
        $machineIds = [];
        $channelCode = trim((string)($postData['channel_code'] ?? ''));
        $supplier = $postData['supplier'] ?? null;
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        unset($postData['channel_code']);
        unset($postData['supplier']);
        //从首页跳转过来携带的是此参数，需要重置下
        if (!empty($postData['create_date']) && empty($postData['pay_time'])) {
            $postData['pay_time'] = $postData['create_date'];
        }
        unset($postData['create_date']);
        if (!empty($postData['machine_group_id'])) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']], 'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }

        $where = $this->getWhere($postData, false, ['trade_no' => "like", "order_type" => "in", "mch_no" => "like", "machine_name" => "like", "machine_id" => "like", "pay_type" => "in", 'factory' => 'in', 'inventory_location' => 'in', 'out_status' => 'in'], 'so.');
        $where['so.pay_status'] = 3;
        $authMch = $this->authMchCannel();
        if ($authMch['status'] != 0) {
            $orderIds = Db::name('sale_orders_details')
                ->whereIn('mc_id', $authMch['data']['mc_id'])
                ->column('order_id');
            $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
            $where[] = ['so.order_id', 'in', $orderIds ?: [0]];
        }
        if ($channelCode !== '') {
            $orderIds = Db::name('sale_orders_details')
                ->where('channel_code', 'like', '%' . $channelCode . '%')
                ->column('order_id');
            $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
            $where[] = ['so.order_id', 'in', $orderIds ?: [0]];
        }
        if (!empty($machineIds)) $where[] = ['so.machine_id', 'in', $machineIds];
        if ($supplier) unset($where['so.ao_id']);
        if ($this->manager['level'] > 3 && !in_array($this->manager['ao_id'], [0, 1])) {
            $where['so.ao_id'] = $this->manager['ao_id'];
        }

        $orderSummary = Db::name('sale_orders')->alias('so')
            ->where($where)
            ->field("
            IFNULL(SUM(so.total_price), 0) total_amount,
            IFNULL(SUM(so.refund_amount), 0) refund_amount,
            COUNT(so.order_id) total_orders,
            IFNULL(SUM(CASE WHEN so.refund_amount > 0 THEN 1 ELSE 0 END), 0) refund_orders")
            ->find();
        if (!is_array($orderSummary)) {
            $orderSummary = [];
        }

        $detailSummary = Db::name('sale_orders')->alias('so')
            ->join('sale_orders_details sod', 'sod.order_id = so.order_id', 'left')
            ->where($where)
            ->field("
            IFNULL(SUM(sod.cost_price * (sod.quantity - sod.refund_quantity)), 0) total_cost_price,
            IFNULL(SUM(sod.quantity), 0) total_quantity,
            IFNULL(SUM(sod.refund_quantity), 0) refund_quantity,
            IFNULL(SUM(CASE sod.is_gift WHEN 1 THEN sod.quantity ELSE 0 END), 0) total_gift")
            ->find();
        if (!is_array($detailSummary)) {
            $detailSummary = [];
        }

        $totalAmount = round($orderSummary['total_amount'] ?? 0, 2);
        $refundAmount = round($orderSummary['refund_amount'] ?? 0, 2);
        $netAmount = round($totalAmount - $refundAmount, 2);
        $totalOrders = (int)($orderSummary['total_orders'] ?? 0);
        $refundOrders = (int)($orderSummary['refund_orders'] ?? 0);
        $totalCostPrice = round($detailSummary['total_cost_price'] ?? 0, 2);
        $totalQuantity = (int)($detailSummary['total_quantity'] ?? 0);
        $refundQuantity = (int)($detailSummary['refund_quantity'] ?? 0);
        $totalGift = (int)($detailSummary['total_gift'] ?? 0);

        // 汇总值
        $totalSaleQuantity = $totalQuantity - $totalGift;
        $netSaleQuantity = $totalSaleQuantity - $refundQuantity;

        return returnData([
            'total_cost_price'      => $hasCostPriceAuth ? round($totalCostPrice, 2) : '--',
            'profit_amount'         => $hasCostPriceAuth ? round($netAmount - $totalCostPrice, 2) : '--',
            'average_retail_price'  => $netSaleQuantity > 0 ? round($netAmount / $netSaleQuantity, 2) : 0,
            'average_cost_price'    => $hasCostPriceAuth ? ($netSaleQuantity > 0 ? round($totalCostPrice / $netSaleQuantity, 2) : 0) : '--',
            'total_amount'          => round($totalAmount, 2),
            'refund_amount'         => round($refundAmount, 2),
            'total_quantity'        => $totalSaleQuantity,
            'refund_quantity'       => $refundQuantity,
            'total_orders'          => $totalOrders,
            'refund_orders'         => $refundOrders,
        ]);
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

    /**
     * 指定设备重新打印订单小票
     * @return array|string
     */
    public function printReceipt()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'printReceipt');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }

        $frequencyKey = 'printReceipt:' . intval($postData['order_id']) . ':' . trim((string)$postData['machine_id']);
        $check = checkFrequency($frequencyKey, 3);
        if ($check !== true) return returnState(100, $check);

        return $this->app->saleOrders->printOrderReceipt($postData);
    }

    /** 后台手动推送已支付订单到微程。 */
    public function manualPushToWeiCheng()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'manualPushToWeiCheng');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $orderId = intval($postData['order_id'] ?? 0);
        $tradeNo = trim((string)($postData['trade_no'] ?? ''));
        if ($orderId <= 0 && $tradeNo === '') return returnState(100, 'order_id和trade_no至少填写一个');

        $frequencyKey = 'manual_push_weicheng_' . ($orderId > 0 ? $orderId : $tradeNo) . '_' . intval($postData['sod_id'] ?? 0);
        $check = checkFrequency($frequencyKey, 3);
        if ($check !== true) return returnState(100, $check);
        return $this->app->saleOrders->manualPushToWeiCheng($postData);
    }

}
