<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/5
 * Time: 14:26
 */

namespace app\AppFactory\Management\Sale;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsHitTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderRefundTrait;
use app\AppFactory\Kernel\Traits\Payment\AliPayTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderRefundTrait;
use app\AppFactory\Kernel\Traits\Payment\JdCashierTrait;
use app\AppFactory\Kernel\Traits\Payment\WxPayTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelNightlyTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleHotelTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersDailyCountTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRefundTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersUnclaimedTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyIncomeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyManagerTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\User\UserTrait;
use app\AppFactory\Management\ManagementClient;
use app\AppFactory\Kernel\Traits\Payment\MallPointsPayTrait;
use app\AppFactory\Kernel\Traits\Mall\MallMachineTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Mall\MallTrait;
use app\AppFactory\Kernel\Traits\Mall\MallRequestLogsTrait;
use app\AppFactory\Kernel\Traits\Payment\BalancePayTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersDetailsDailyCountTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrgMachineChannelTrait;
use think\facade\Db;

class SaleOrdersClient extends ManagementClient
{
    use AuthManagerTrait, AuthOrgMachineChannelTrait;
    use SaleOrdersTrait, SaleOrdersRefundTrait, SaleOrdersRevenueTrait, SaleOrdersUnclaimedTrait, SaleOrdersDailyCountTrait, SaleHotelTrait, SaleHotelNightlyTrait,SaleOrdersDetailsDailyCountTrait;
    use BeforeOrderPaymentTrait;
    use MallPointsPayTrait,MallMachineTrait,MachineTrait,MallTrait,MallRequestLogsTrait;
    use StrategyMachineTrait;
    use StrategyIncomeTrait;
    use StrategyManagerTrait;
    use UserTrait;
    use StrategyPayeeTrait;
    use BeforeOrderRefundTrait, AfterOrderRefundTrait;
    use WxPayTrait, AliPayTrait, JdCashierTrait;
    use GoodsHitTrait;
    use BalancePayTrait;

    public $order;
    public $sod;
    public $strategyPayee;
    public $refundData;
    public $refund_no;
    public $refundTradeNo;
    public $data;

    /**
     * @var array 退款类型
     */
    protected $refundType = [
        "1" => "wxRefund",
        "11" => "wxRefund",
        "12" => "wxRefund",
        "2" => "aliRefund",
        "21" => "aliRefund",
        "22" => "aliRefund",
        "3" => "tlRefund",
        "4" => "jdRefund",
//        "8" => "CoGoRefund",
        "9" => "mallPointsRefund",
        "20" => "balanceRefund",
    ];

    protected $postData;
    protected $totalRefundMoney;

    /**
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param bool $supplier
     * @return array|\think\response\Json
     */
    public function getSoList($where, $pageNum = 0, $field = "*", $order = "", $supplier = false)
    {
        //检查当前登录用组织是否租赁设备
        // $authOrgMc = $this->getAuthOrgMCCount(['ao_id' => $this->manager['ao_id']]);
        // if($authOrgMc) return $this->getGerSoList($where, $pageNum, $field, $order, $this->manager['ao_id']);
        try {
            if($supplier){
                if ($this->manager['pid'] > 0) {
                    $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
                    if ($mIds) $where[] = ['m_id', 'in', $mIds];
                }
            }
            $data = $this->getSaleOrdersList($where, $pageNum, $field, $order, $supplier)->toArray();
            return $this->r(200, $this->lang("query_success"), $data);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    
    public function getGerSoList($where, $pageNum = 0, $field = "*", $order = "", $detailAoId = 0)
    {
        try {
            if ($detailAoId) {
                $orderIds = array_values(array_unique($this->getSaleOrdersDetailsColumn(['sod_ao_id' => $detailAoId], 'order_id')));
                if (!$orderIds) $orderIds = [0];
                $where[] = ['order_id', 'in', $orderIds];
            }
            $data = $this->getSaleOrdersList($where, $pageNum, $field, $order)->toArray();

            if ($detailAoId) {
                $data = $this->filterSaleOrdersByDetailAoId($data, $detailAoId, $pageNum);
            }
            return $this->r(200, $this->lang("query_success"), $data);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    protected function filterSaleOrdersByDetailAoId($data, $detailAoId, $pageNum = 0)
    {
        $rows = $pageNum ? ($data['data'] ?? []) : $data;

        foreach ($rows as $key => $row) {
            $details = obj2arr($row['details'] ?? []);
            $details = array_values(array_filter($details, function ($detail) use ($detailAoId) {
                return isset($detail['sod_ao_id']) && (string) $detail['sod_ao_id'] === (string) $detailAoId;
            }));

            if (!$details) {
                unset($rows[$key]);
                continue;
            }

            $rows[$key]['details'] = $details;
        }

        $rows = array_values($rows);
        if ($pageNum) {
            $data['data'] = $rows;
            return $data;
        }

        return $rows;
    }

    public function getDetailsList($where, $pageNum = 0, $field = "*", $order = "", $supplier = false)
    {
        if($supplier){
            if ($this->manager['pid'] > 0) {
                $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
                if ($mIds) $where[] = ['m_id', 'in', $mIds];
            }
        }
        return $this->r(200, 'query_success', $this->getSaleOrdersDetailsJoinOrderList($where, $pageNum, $field, $order));

    }

    public function getQueryConditionOptions($where = [])
    {
        try {
            $baseQuery = Db::name('sale_orders')->where($where)->whereRaw("pay_status in ('3','7')");

            $payTypes = (clone $baseQuery)
                ->whereRaw('pay_type IS NOT NULL')
                ->distinct(true)
                ->order('pay_type asc')
                ->column('pay_type');

            $payChannels = (clone $baseQuery)
                ->whereRaw('pay_channel IS NOT NULL')
                ->field('pay_channel,pay_channel_name')
                ->distinct(true)
                ->order('pay_channel asc')
                ->select()
                ->toArray();

            $data = [
                'pay_type_list' => [],
                'pay_channel_list' => [],
            ];
            $payTypeExists = [];
            $payChannelExists = [];

            foreach ($payTypes as $payType) {
                $payType = intval($payType);
                if (isset($payTypeExists[$payType])) continue;
                $payTypeExists[$payType] = 1;
                $data['pay_type_list'][] = [
                    'value' => $payType,
                    'label' => $this->formatPayType($payType),
                ];
            }

            $payChannelMap = $this->getPayChannelNameMap();
            foreach ($payChannels as $item) {
                $payChannel = intval($item['pay_channel'] ?? 0);
                if (isset($payChannelExists[$payChannel])) continue;
                $payChannelExists[$payChannel] = 1;
                $label = trim((string)($item['pay_channel_name'] ?? ''));
                if ($label === '') {
                    $label = $payChannelMap[$payChannel] ?? '其他';
                }
                $data['pay_channel_list'][] = [
                    'value' => $payChannel,
                    'label' => $label,
                ];
            }

            return $this->r(200, $this->lang("query_success"), $data);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    protected function buildSqlCaseByMap($column, $map, $defaultPrefix)
    {
        $cases = [];
        foreach ($map as $value => $label) {
            $cases[] = "WHEN " . intval($value) . " THEN '" . str_replace("'", "''", $label) . "'";
        }
        return "(CASE {$column} " . implode(' ', $cases) . " ELSE CONCAT('" . str_replace("'", "''", $defaultPrefix) . "',{$column}) END)";
    }

    protected function buildPayTypeCaseSql($column)
    {
        return $this->buildSqlCaseByMap($column, $this->getPayTypeNameMap(), '支付类型#');
    }

    protected function buildPayMethodCaseSql($column)
    {
        return $this->buildSqlCaseByMap($column, $this->getPayMethodNameMap(), '支付方式#');
    }

    /**
     * 发起订单退款
     * @param $postData
     * @return array|bool|string
     */
    public function refundOrder($postData)
    {
        $this->order = $this->getSaleOrdersFind(['order_id' => $postData['order_id']]);
        if (!$this->order) return $this->rFail($this->lang("VSaleOrdersRefund.order_no_data"));
        $this->order = $this->order->toArray();
        if (in_array($this->order['pay_type'],  [0,5,6,7,8])) return $this->r(100, $this->lang("VSaleOrders.free_can_not_refund"));
        $checkRefund = $this->getSaleOrdersRefundFind(['order_id' => $postData['order_id'],'status' => 1]);
        if ($checkRefund) {
            if ($checkRefund['create_time'] >= time() - 3600) {
                return $this->rFail($this->lang("VSaleOrdersRefund.refunding") . ": " . $checkRefund['remark']);
            }
            $this->delSaleOrdersRefund(['sor_id' => $checkRefund['sor_id']]);
        }
        // 不在数组内的需要查询支付配置
        if (!in_array($this->order['pay_type'],  [8])) {
            $check = $this->getSPayee();
            actionLog($this->strategyPayee, '收款策略数据');
            if ($check !== true) {
                return $check;
            }
        }
        $this->startTrans();
        try {
            $this->postData = $postData;// 生成退款记录
            $flag = $this->createSor();
            if (!is_array($flag)) {
                $this->rollbackTrans();
                return $flag;
            }
            $result = flag_check($flag);
            if ($result) {
                $this->commitTrans();
                // 调用平台退款
                return $this->callRefund();
            }
            $this->rollbackTrans();
            return $this->rFail("退款失败：生成退款记录失败");
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 获取收款策略
     * @return array|bool|string
     */
    public function getSPayee()
    {
        $where['sm.s_type'] = 1;
        $where['sp.sp_id'] = $this->order['sp_id'];
        if($this->order['ao_id'] == 19){
            $where['sm.ao_id'] = $this->order['ao_id'];
        }
        $strategyPayee = $this->getStrategyPayeeContent($where, 'sp.*');
        if ((!is_array($strategyPayee) || !$strategyPayee) && in_array(intval($this->order['pay_type']), [11, 12, 21, 22], true)) {
            $where['sp.payee_type'] = in_array(intval($this->order['pay_type']), [11, 12], true) ? 1 : 2;
            $strategyPayee = $this->getStrategyPayeeContent($where, 'sp.*');
        }
        if (!is_array($strategyPayee)) return $strategyPayee;
        if (!$strategyPayee) return $this->rFail("查无收款方配置信息");
        if (!in_array($strategyPayee['payee_type'], array_keys($this->refundType))) {
            return $this->rFail("未定义的支付类型");
        }
        $this->strategyPayee = $strategyPayee;
        return true;
    }

    /**
     * 调用退款平台接口
     * @return bool
     */
    public function callRefund()
    {
        // 调用平台退款接口
        $this->refundData = [
            "order_trade_no" => $this->order['trade_no'],
            "refund_trade_no" => $this->refundTradeNo,
            "refund_amount" => $this->totalRefundMoney,
            "remark" => $this->postData['remark'],
        ];
//        return $this->r(100,'测试中，暂停使用',$this->refundData);
        $func_name = $this->refundType[$this->order['pay_type']];
        $result = $this->$func_name();
        $check = obj2arr($result);
        actionLog($check, '退款结果');
        if ($check['state'] == "200") {
            // 支付宝支付、通联支付退款实时处理，不用异步
            if(in_array($this->order['pay_type'], [2,21,22,3,9,20])){
                $this->startTrans();
                try {
                    $this->data['refundAmount'] = $this->totalRefundMoney;
                    if (isset($check['data']['trxid'])) $this->refund_no = $check['data']['trxid'];
                    if (isset($check['data']['trade_no'])) $this->refund_no = $check['data']['trade_no'];
                    $end = $this->refundSuccess();
                    actionLog($end, '处理退款成功结果');
                    if ($end !== true) {
                        $this->rollbackTrans();
                        return $end;
                    }
                    $this->commitTrans();
                } catch (\Exception $e) {
                    $this->rollbackTrans();
                    actionException($e, 1);
                }
            }
            return $result;
        } else {
            $this->updateSaleOrders(['order_id' => $this->order['order_id'],'refund_status' => 3]);
            $end = $this->refundFail();
            if ($end !== true) return $end;
        }
        return $result;
    }

    /**
     * 获取首页今天昨天销售额、销量
     * @param $where
     * @return array
     */
    public function getData($where)
    {
        $data = [
            "today" => ["saleMoney" => 0.00, "saleQuantity" => 0, 'discountMoney' => 0],
            "yesterday" => ["saleMoney" => 0.00, "saleQuantity" => 0, 'discountMoney' => 0],
            "thisMonth" => ["saleMoney" => 0.00, "saleQuantity" => 0, 'discountMoney' => 0],
        ];
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            if ($mIds) {
                $where[] = ['m_id', 'in', $mIds];
            }
        }
        $whereToday = $where;
        $whereToday[] = ['create_date', '=', strtotime(date("Y-m-d"))];
        $today = $this->getSaleOrdersFind($whereToday, 'sum(total_price) saleMoney,sum(total_quantity) saleQuantity,sum(discount_price) discountMoney', '', 'create_date');
        if ($today) $today = $today->toArray();

        $whereYesterday = $where;
        $whereYesterday[] = ['create_date', '=', strtotime(date("Y-m-d 00:00:00", strtotime("-1 days")))];
        $yesterday = $this->getSaleOrdersFind($whereYesterday, 'sum(total_price) saleMoney,sum(total_quantity) saleQuantity,sum(discount_price) discountMoney', '', 'create_date');
        
        $whereThisMonth = $where;
        $whereThisMonth[] = ['create_date', '>=', strtotime(date("Y-m-01"))];
        $thisMonth = $this->getSaleOrdersFind($whereThisMonth, 'sum(total_price) saleMoney,sum(total_quantity) saleQuantity,sum(discount_price) discountMoney');

        if ($yesterday) $yesterday = $yesterday->toArray();
        if ($today) $data['today'] = $today;
        if ($yesterday) $data['yesterday'] = $yesterday;
        if ($thisMonth) $data['thisMonth'] = $thisMonth;
        return $data;
    }

    /**
     * 获取销售视图数据
     * 销售额、销量折线图
     * 默认1个月内每天的数据
     * 半年内每周的数据
     * 1年内每月的数据
     * @param $where
     * @param int $type
     * @return array|string
     */
    public function getChartData($where, $type = 1)
    {
        $field = "";
        $group = "";
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            if ($mIds) {
                $where[] = ['m_id', 'in', $mIds];
            }
        }
        if ($type == 1) {
            $field = "SUM(totalPrice - totalRefundAmount) totalPrice,SUM(totalQuantity - totalRefundQuantity) totalQuantity,countDate";
            $group = "create_date";
            $where[] = ['create_date', '>=', strtotime("-1 months")];
        }
        if ($type == 2) {
            $field = "sum(totalPrice - totalRefundAmount) totalPrice, sum(totalQuantity - totalRefundQuantity) totalQuantity, DATE_FORMAT(countDate,'Week %v,%x') week";
            $group = "week";
            $where[] = ['create_date', '>=', strtotime("-15 week")];
        }
        if ($type == 3) {
            $field = "sum(totalPrice - totalRefundAmount) totalPrice, sum(totalQuantity - totalRefundQuantity) totalQuantity, DATE_FORMAT(countDate,'%x-%m') month";
            $group = "month";
            $where[] = ['create_date', '>=', strtotime("-12 month")];
        }
        $data = $this->getSaleOrdersDailyCountList($where, 0, $field, '', $group);
        return $this->rQ($data);
    }

    /**
     * 导出订单数据
     * @param $where
     * @return array|string
     * @throws \Exception
     */
    public function exportSo($where)
    {
        $mIds = [];
        $payTypeCase = $this->buildPayTypeCaseSql('pay_type');
        $soPayTypeCase = $this->buildPayTypeCaseSql('so.pay_type');
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            if ($mIds) $where[] = ['m_id', 'in', $mIds];
        }
        $where['raw'] = "pay_status in ('3', '7')";
        $field = <<<SQL
order_id,machine_id,machine_name,pay_status,trade_no,mch_no,total_quantity,total_price,discount_price,retail_price,factory,inventory_location,
                (CASE order_type 
                    WHEN 1 THEN "普通订单"
                    WHEN 2 THEN "优惠券订单"
                    WHEN 3 THEN "取货码订单"
                    WHEN 4 THEN "盲盒活动"
                    WHEN 5 THEN "满减满送活动"
                    WHEN 6 THEN "叠加营销活动"
                    END 
                ) order_type,
                IFNULL(NULLIF(pay_channel_name,""),(CASE pay_channel 
                    WHEN 1 THEN "微程小程序订单"
                    WHEN 2 THEN "机械车小程序订单"
                    WHEN 3 THEN "售卖机会员积分订单"
                    WHEN 4 THEN "商场积分订单"
                    WHEN 5 THEN "取货码订单"
                    WHEN 6 THEN "余额支付订单"
                    WHEN 7 THEN "微信支付"
                    WHEN 8 THEN "支付宝支付"
                    WHEN 9 THEN "POS/刷卡支付"
                    WHEN 10 THEN "现金支付"
                    WHEN 11 THEN "其他"
                    ELSE "其他" END)) pay_channel,
                (CASE out_status 
                    WHEN 1 THEN 
                        "正常"
                    WHEN 2 THEN 
                        "已发出货命令"
                    WHEN 3 THEN 
                        "设备已接收"
                    WHEN 4 THEN 
                        (CASE refund_status WHEN 1 THEN "正常" WHEN 2 THEN "已退款" WHEN 3 THEN "退款失败" END)
                    WHEN 5 THEN
                        "出货失败"
                    WHEN 6 THEN 
                        "未取商品"
                    END 
                ) refund_status,
                {$payTypeCase} pay_type,
                FROM_UNIXTIME(pay_time,"%Y-%m-%d %H:%i:%s") pay_time,
                FROM_UNIXTIME(out_time,"%Y-%m-%d %H:%i:%s") out_time
SQL;
        $list = $this->getSaleOrdersList($where, 0, $field);
        if ($list) {
            $list = $list->toArray();
            $postData = input();
            $whereRefund['status'] = 2;
            if ($mIds) $whereRefund[] = ['sor.m_id', 'in', $mIds];
            if (isset($postData['m_id']) && $postData['m_id']) {
                $whereRefund['sor.m_id'] = $postData["m_id"];
            }
            if (isset($postData['mch_no']) && $postData['mch_no']) {
                $whereRefund[] = ['so.mch_no', "like", "%" . $postData['mch_no'] . "%"];
            }
            if (isset($postData['trade_no']) && $postData['trade_no']) {
                $whereRefund[] = ['sor.trade_no', "like", "%" . $postData['trade_no'] . "%"];
            }
            if (isset($postData['pay_time']) && $postData['pay_time']) {
                $time = explode("~", $postData['pay_time']);
                $whereRefund[] = ['sor.update_time', 'between', [strtotime($time[0]), strtotime($time[1])]];
            }
            if (isset($postData['machine_name']) && $postData['machine_name'])
                $whereRefund[] = ['sor.machine_name', 'like', "%" . $postData['machine_name'] . "%"];

            // 添加时间链表查询条件
            if (isset($postData['out_time']) && $postData['out_time']){
                $out_time = explode('~',$postData['out_time']);
                $out_time1 = strtotime($out_time[0]);
                $out_time2 = strtotime($out_time[1]);
                $whereRefund[] = ['so.out_time','between',[$out_time1,$out_time2]];
            }

            if (isset($postData['pay_time']) && $postData['pay_time']){
                $pay_time = explode('~',$postData['pay_time']);
                $pay_time1 = strtotime($pay_time[0]);
                $pay_time2 = strtotime($pay_time[1]);
                $whereRefund[] = ['so.pay_time','between',[$pay_time1,$pay_time2]];
            }

            $refundField = <<<SQL
sor.order_id,sor.machine_id,sor.machine_name,sor.trade_no,so.mch_no,so.factory,so.inventory_location,
            sor.refund_quantity total_quantity,
             (0-sor.refund_amount) total_price,("-") discount_price,("-") retail_price,
             ("已退款") refund_status,
                (CASE order_type 
                    WHEN 1 THEN "普通订单"
                    WHEN 2 THEN "优惠券订单"
                    WHEN 3 THEN "取货码订单"
                    WHEN 4 THEN "盲盒活动"
                    WHEN 5 THEN "满减满送活动"
                    WHEN 6 THEN "叠加营销活动"
                    END 
                ) order_type,
                IFNULL(NULLIF(so.pay_channel_name,""),(CASE so.pay_channel
                    WHEN 1 THEN "微程小程序订单"
                    WHEN 2 THEN "机械车小程序订单"
                    WHEN 3 THEN "售卖机会员积分订单"
                    WHEN 4 THEN "商场积分订单"
                    WHEN 5 THEN "取货码订单"
                    WHEN 6 THEN "余额支付订单"
                    WHEN 7 THEN "微信支付"
                    WHEN 8 THEN "支付宝支付"
                    WHEN 9 THEN "POS/刷卡支付"
                    WHEN 10 THEN "现金支付"
                    WHEN 11 THEN "其他"
                    ELSE "其他" END)) pay_channel,
                {$soPayTypeCase} pay_type,
                FROM_UNIXTIME(sor.update_time,"%Y-%m-%d %H:%i:%s") pay_time,("-") out_time
SQL;
            $refund = $this->getSaleOrdersRefundListJoinSo($whereRefund, 0, $refundField, 'sor.update_time asc');
            if ($refund) $list = array_merge($list, $refund->toArray());
            $title = [
                "order_id" => "订单ID",
                "machine_id" => "设备编号",
                "machine_name" => "设备名称",
                "trade_no" => "订单编号",
                "mch_no" => "支付编号",
                "total_quantity" => "订单总数",
                "total_price" => "支付金额",
                "discount_price" => "优惠金额",
                "retail_price" => "原订单总额",
                'factory' => "所属工厂",
                'inventory_location' => "库存地点",
                "refund_status" => "订单状态",
                "order_type" => "订单类型",
                "pay_channel" => "订单分类",
                "pay_type" => "支付类型",
                "pay_time" => "支付时间",
                "out_time" => "出货时间",
            ];
            $filename = "订单交易-" . date("Ymd");
            return $this->sendToExport("订单管理-销售订单", $filename, $title, $list);
        }
        return $this->rFail($this->lang("action_fail"));
    }

    /**
     * 导出商品交易
     * @param $where
     * @return array|string
     */
    public function exportGoodsSo($where)
    {
        $soPayTypeCase = $this->buildPayTypeCaseSql('so.pay_type');
        $soPayMethodCase = $this->buildPayMethodCaseSql('so.pay_method');
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            if ($mIds) $where[] = ['so.m_id', 'in', $mIds];
        }
        $field = "so.machine_id,so.machine_name,so.trade_no,so.mch_no,sod.sku,sod.g_name,sod.channel_code,sod.retail_price,sod.discount_price,
        sod.total_sod_price,so.factory,so.inventory_location,
            (CASE so.out_status WHEN 2 THEN '已发出货命令' WHEN 3 THEN '设备已接收' WHEN 4 THEN '出货成功' WHEN 5 THEN '出货失败' END) out_status,
            (CASE so.order_type 
            WHEN 1 THEN '普通订单' 
            WHEN 2 THEN '优惠券订单'
            WHEN 3 THEN '取货码订单'
            WHEN 4 THEN '付费抽奖订单'
            WHEN 5 THEN '满减满送订单'
            WHEN 6 THEN '叠加营销活动订单'
            ELSE '' END) order_type,
            IFNULL(NULLIF(so.pay_channel_name,''),(CASE so.pay_channel
            WHEN 1 THEN '微程小程序订单'
            WHEN 2 THEN '机械车小程序订单'
            WHEN 3 THEN '售卖机会员积分订单'
            WHEN 4 THEN '商场积分订单'
            WHEN 5 THEN '取货码订单'
            WHEN 6 THEN '余额支付订单'
            WHEN 7 THEN '微信支付'
            WHEN 8 THEN '支付宝支付'
            WHEN 9 THEN 'POS/刷卡支付'
            WHEN 10 THEN '现金支付'
            WHEN 11 THEN '其他'
            ELSE '其他' END)) pay_channel,
            {$soPayTypeCase} pay_type,
            {$soPayMethodCase} pay_method,
            
            (CASE out_status 
                WHEN 1 THEN 
                    \"正常\"
                WHEN 2 THEN 
                    \"已发出货命令\"
                WHEN 3 THEN 
                    \"设备已接收\"
                WHEN 4 THEN 
                   (CASE WHEN so.refund_amount > 0 THEN so.refund_amount ELSE '正常' END)
                WHEN 5 THEN
                    \"出货失败\"
                WHEN 6 THEN 
                    \"未取商品\"
                END 
            ) order_status,
            FROM_UNIXTIME(so.pay_time,'%Y-%m-%d %H:%i:%s') pay_time,
            FROM_UNIXTIME(so.out_time,'%Y-%m-%d %H:%i:%s') out_time,
            (sod.quantity) quantity,
            (sod.success_quantity) success_quantity";
        $list = $this->getSaleOrdersDetailsJoinOrderList($where, 0, $field);
        if ($list) {
            $list = $list->toArray();
            if ($list) {
                $where['sor.status'] = 2;
                if (isset($where[0][0]) && strpos($where[0][0],"create_time") !== false) $where[0][0] = "sor.update_time";
                $refund = $this->getSaleOrdersRefundListJoinSoSod($where, 0,
                    "sor.machine_id,sor.machine_name,sor.trade_no,so.mch_no,so.factory,so.inventory_location,sod.sku,sor.g_name,sor.channel_code,sod.retail_price,sod.discount_price,(0-sor.refund_amount) total_sod_price,
                            (CASE so.out_status WHEN 1 THEN '待取货' WHEN 2 THEN '已发出货命令' WHEN 3 THEN '设备已接收' WHEN 4 THEN '出货成功' WHEN 5 THEN '出货失败' END) out_status,
                        (CASE so.order_type 
                        WHEN 1 THEN '普通订单' 
                        WHEN 2 THEN '优惠券订单'
                        WHEN 3 THEN '取货码订单'
                        WHEN 4 THEN '付费抽奖订单'
                        WHEN 5 THEN '满减满送订单'
                        WHEN 6 THEN '叠加营销活动订单'
                        ELSE '' END) order_type,
                        IFNULL(NULLIF(so.pay_channel_name,''),(CASE so.pay_channel
                        WHEN 1 THEN '微程小程序订单'
                        WHEN 2 THEN '机械车小程序订单'
                        WHEN 3 THEN '售卖机会员积分订单'
                        WHEN 4 THEN '商场积分订单'
                        WHEN 5 THEN '取货码订单'
                        WHEN 6 THEN '余额支付订单'
                        WHEN 7 THEN '微信支付'
                        WHEN 8 THEN '支付宝支付'
                        WHEN 9 THEN 'POS/刷卡支付'
                        WHEN 10 THEN '现金支付'
                        WHEN 11 THEN '其他'
                        ELSE '其他' END)) pay_channel,
                        {$soPayTypeCase} pay_type,
                        {$soPayMethodCase} pay_method,
                        ('已退款') order_status,
                        FROM_UNIXTIME(sor.update_time,'%Y-%m-%d %H:%i:%s') pay_time,
                        FROM_UNIXTIME(so.out_time,'%Y-%m-%d %H:%i:%s') out_time,
                        (sor.refund_quantity) quantity,
                        (sod.success_quantity) success_quantity");
                if ($refund) $list = array_merge($list, $refund->toArray());
                $title = [
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "trade_no" => "交易号",
                    "mch_no" => "支付编号",
                    "sku" => "SKU",
                    "g_name" => "商品名称",
                    "channel_code" => "槽位",
                    "retail_price" => "单价",
                    "discount_price" => "优惠价",
                    "total_sod_price" => "支付金额",
                    'factory' => "所属工厂",
                    'inventory_location' => "库存地点",
                    "out_status" => "出货状态",
                    "order_status" => "订单状态",
                    "order_type" => "订单类型",
                    "pay_channel" => "订单分类",
                    "pay_type" => "支付类型",
                    "pay_method" => "支付方式",
                    "pay_time" => "支付时间",
                    "out_time" => "出货时间",
                    "quantity" => "商品总数",
                    "success_quantity" => "出货成功数量",
                ];
                $filename = "商品交易列表-" . date("YmdHis");
                return $this->sendToExport("订单管理-销售订单", $filename, $title, $list);
            }
        }
        return $this->rFail($this->lang("action_fail"));
    }

    /**
     * 导出退款记录列表
     * @param $where
     * @return array|string
     */
    public function exportRefund($where)
    {
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            if ($mIds) $where[] = ['sor.m_id', 'in', $mIds];
        }
        $field = "sor.machine_id,sor.machine_name,sor.trade_no,so.mch_no,
                sor.refund_trade_no,
                (CASE sod.channel_position WHEN 1 THEN '主柜' WHEN 2 THEN '副柜' END ) channel_position,
                sod.channel_code,
                sod.g_name,
                sor.refund_amount,
                sor.refund_quantity,
                (CASE sor.status WHEN 1 THEN '已提交退款申请' WHEN 2 THEN '退款成功' WHEN 3 THEN '退款失败' END) status,
                sor.remark
                ";
        $list = $this->getSaleOrdersRefundListJoinSoSod($where, 0, $field, "sor_id desc");
        if ($list) {
            $list = $list->toArray();
            $title = [
                "machine_id" => "设备编号",
                "machine_name" => "设备名称",
                "trade_no" => "订单编号",
                "mch_no" => "支付编号",
                "refund_trade_no" => "退款编号",
                "channel_position" => "货道位置",
                "channel_code" => "槽位",
                "g_name" => "商品名称",
                "refund_amount" => "退款金额",
                "refund_quantity" => "退款数量",
                "status" => "退款状态",
                "remark" => "备注",
            ];
            $filename = "退款交易列表-" . date("Ymd");
            return $this->sendToExport("订单管理-销售订单", $filename, $title, $list);
        }
        return $this->rFail($this->lang("action_fail"));
    }

    /**
     * 获取销售报表汇总
     * @param $where
     * @param string $field
     * @param string $order
     * @param string $group
     * @return array|\think\response\Json
     */
    public function getTotalReport($where, $field = "*", $order = "", $group = "")
    {
        $data = $this->getSaleOrdersDailyCountFind($where, $field, $order, $group);
        return $this->rQ($data);
    }

    /**
     * 销售报表
     * @param $where
     * @param $pageNum
     * @param string $order
     * @param string $group
     * @return array|\think\response\Json
     */
    public function getReportList($where, $pageNum, $order = "", $group = "")
    {
        $field = "countDate,";
        if ($group) {
            // 日
            if ($group == "day") {
                $field = "countDate,";
                $group = "countDate";
            }
            // 月
            if ($group == "month") {
                $field = "DATE_FORMAT(countDate ,'%Y-%m') countDate,";
                $group = " DATE_FORMAT(countDate ,'%Y-%m')";
            }
            // 年
            if ($group == "year") {
                $field = "DATE_FORMAT(countDate ,'%Y') countDate,";
                $group = "DATE_FORMAT(countDate ,'%Y') ";
            }
        }
        if (!$group) $group = "create_date";
        $field = $field . "
        SUM(totalPrice) totalPrice,
        SUM(lotteryAmount) lotteryAmount,
        sum(totalRefundAmount) totalRefundAmount,
        SUM(totalPrice - totalRefundAmount) totalSalePrice,
        SUM(totalQuantity - totalRefundQuantity) totalSaleQuantity,
        SUM(order_num) order_num,
        SUM(totalDiscountPrice) totalDiscountPrice,
        SUM(giftQuantity) giftQuantity";
        $order = 'create_date desc';
        $data = $this->getSaleOrdersDailyCountList($where, $pageNum, $field, $order, $group);
        return $this->rQ($data);
    }

    /**
     * 导出销售报表
     * @param $where
     * @param string $order
     * @return array|\think\response\Json
     */
    public function exportReport($where, $order = "")
    {
        $field = "countDate,
        SUM(totalPrice) totalPrice,
        SUM(lotteryAmount) lotteryAmount,
        sum(totalRefundAmount) totalRefundAmount,
        SUM(totalPrice - totalRefundAmount) totalSalePrice,
        SUM(totalQuantity - totalRefundQuantity) totalSaleQuantity,
        SUM(order_num) order_num,
        SUM(totalDiscountPrice) totalDiscountPrice,
        SUM(giftQuantity) giftQuantity";
        $group = "create_date";
        $list = $this->getSaleOrdersDailyCountList($where, 0, $field, $order, $group);
        if ($list) {
            $list = $list->toArray();
            $title = [
                "countDate" => "日期",
                "totalPrice" => "设备销售额",
                "lotteryAmount" => "抽奖销售额",
                "totalRefundAmount" => "退款金额",
                "totalSalePrice" => "实际销售金额",
                "totalSaleQuantity" => "实际销售量",
                "order_num" => "订单总数",
                "totalDiscountPrice" => "优惠金额",
                "giftQuantity" => "赠品数量",
            ];
            $filename = "导出销售报表_" . date("Ymd");
            return $this->sendToExport("订单管理-销售报表", $filename, $title, $list);
        }
        return $this->rFail();
    }

    /**
     * 礼品赠送数量，今天/昨天
     * @param $where
     * @return array|\think\response\Json
     */
    public function getGift($where)
    {
        if ($this->manager['pid'] > 0) {
            $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
            if ($mIds) {
                $where[] = ['m_id', 'in', $mIds];
            }
        }
        $where['sod.is_gift'] = 1;
        $where['so.out_status'] = 4;
        $whereToday = $where;
        $whereToday[] = ['so.create_time', 'between', [strtotime(date("Y-m-d 00:00:00")), strtotime(date("Y-m-d 23:59:59"))]];
        $today = $this->joinSaleOrdersSum($whereToday, 'sod.quantity');
        $whereYesterday = $where;
        $whereYesterday[] = ['so.create_time', 'between', [strtotime(date("Y-m-d 00:00:00", strtotime("-1 days"))), strtotime(date("Y-m-d 23:59:59", strtotime("-1 days")))]];
        $yesterday = $this->joinSaleOrdersSum($whereYesterday, 'sod.quantity');

        $data = [
            "today" => $today,
            "yesterday" => $yesterday,
        ];
        return $this->rQ($data);
    }

    /**
     *  销售数据概况
     *  totalQuantity 总销售量（包含赠品）
     *
     *  totalClick           点击次数
     *  clickConversionRate  点击转化率
     *  totalSaleQuantity    实际销售量 = 总销售量 - 赠品数量
     *  totalPrice           实际销售额 = SUM（实际支付金额 - 退款金额）
     *  totalDiscountPrice   总优惠额
     *  totalGift            赠品数量
     *  totalCostPrice       总成本
     *  profitAmount         利润额 = 实际销售额 - 总成本
     *  averageRetailPrice   平均售价 = 实际销售额 / 实际销售量
     *  averageCostPrice     平均成本价 = 总成本 /  实际销售量
     *  grossProfitRate      毛利率 = 利润额 / 总销售额
     *
     * @param $where
     * @return array|\think\response\Json
     */
    public function saleDataCollect($where)
    {
        $whereCollect = $where;
        $whereCollect['so.pay_status'] = 3;
        $field = "
        IFNULL(sum(sod.quantity - sod.refund_quantity),0) totalQuantity,
        IFNULL(sum(sod.total_sod_price - sod.refund_amount),0) totalPrice,
        IFNULL(sum(sod.discount_price),0) totalDiscountPrice,
        IFNULL(sum(sod.total_sod_price),0) totalSalePrice,
        IFNULL(sum(case sod.is_gift WHEN 1 THEN sod.quantity ELSE 0 END),0) totalGift,
        IFNULL(sum(sod.cost_price * (sod.quantity - sod.refund_quantity)),0) totalCostPrice
        ";
        $collectData = $this->getSaleOrdersDetailsData($whereCollect, $field)->toArray();
        actionLog($this->getLS(), '【SQL】统计概况');
        $collectData['totalSaleQuantity'] = bcsub($collectData['totalQuantity'], $collectData['totalGift']);
        $whereGIds = $where;
        $whereGIds[] = ['g_id', ">", 0];
        $gIds = $this->joinSoSodColumn($whereGIds, 'g_id', 'g_id');
        $collectData['totalClick'] = $this->getGoodsHitCount(['g_id' => $gIds]) ?? 0;
        $collectData['clickConversionRate'] = $collectData['totalClick'] > 0 ? bcmul(bcdiv($collectData['totalSaleQuantity'], $collectData['totalClick'], 4), 100, 2) . "%" : "0%";
        $collectData['profitAmount'] = bcsub($collectData['totalPrice'], $collectData['totalCostPrice'], 2);
        $collectData['averageRetailPrice'] = $collectData['totalSaleQuantity'] > 0 ? bcdiv($collectData['totalPrice'], $collectData['totalSaleQuantity'], 2) : 0.00;
        $collectData['averageCostPrice'] = $collectData['totalSaleQuantity'] > 0 ? bcdiv($collectData['totalCostPrice'], $collectData['totalSaleQuantity'], 2) : 0.00;
        $collectData['grossProfitRate'] = $collectData['totalPrice'] > 0 ? bcmul(bcdiv($collectData['profitAmount'], $collectData['totalPrice'], 4), 100, 2) . "%" : "0%";
        unset($collectData['totalQuantity']);
        return $this->r(200, $this->lang("query_success"), $collectData);
    }

    /**
     *  销售数据列表
     *  totalQuantity 总销售量（包含赠品）
     *
     *  machine_id           设备编号
     *  machine_name         设备名称
     *  sku                  商品SKU
     *  g_name               商品名称
     *  totalClick           点击次数
     *  clickConversionRate  点击转化率
     *  totalSaleQuantity    实际销售量 = 总销售量 - 赠品数量
     *  totalPrice           实际销售额 = SUM（实际支付金额 - 退款金额）
     *  totalDiscountPrice   总优惠额
     *  totalGift            赠品数量
     *  totalCostPrice       总成本
     *  profitAmount         利润额 = 实际销售额 - 总成本
     *  averageRetailPrice   平均售价 = 实际销售额 / 实际销售量
     *  averageCostPrice     平均成本价 = 总成本 /  实际销售量
     *  grossProfitRate      毛利率 = 利润额 / 总销售额
     *
     * @param array|string $where
     * @param int $pageNum 页面数据条数
     * @return array|\think\response\Json
     */
    public function saleDataCollectList($where, $pageNum = 0)
    {
        $field = "
        sod.g_id,so.machine_id,so.machine_name,sod.sku,sod.g_name,
        IFNULL(sum(sod.quantity - sod.refund_quantity),0) totalQuantity,
        IFNULL(sum(sod.total_sod_price - sod.refund_amount),0) totalPrice,
        IFNULL(sum(sod.total_sod_price),0) totalSalePrice,
        IFNULL(sum(sod.discount_price),0) totalDiscountPrice,
        IFNULL(sum(case sod.is_gift WHEN 1 THEN sod.quantity ELSE 0 END),0) totalGift,
        IFNULL(sum(sod.cost_price * (sod.quantity - sod.refund_quantity)),0) totalCostPrice
        ";
        $collectList = $this->getSaleOrdersDetailsJoinOrderList($where, $pageNum, $field, 'totalPrice desc', 'm_id,g_id');
        actionLog($this->getLS(), '统计销售数据');
        $collectList = $collectList->each(function ($collectData) {
            $collectData['totalSaleQuantity'] = bcsub($collectData['totalQuantity'], $collectData['totalGift']);
            $collectData['totalClick'] = $this->getGoodsHitCount(['g_id' => $collectData['g_id']]) ?? 0;
            $collectData['clickConversionRate'] = $collectData['totalClick'] > 0 ? bcmul(bcdiv($collectData['totalSaleQuantity'], $collectData['totalClick'], 4), 100, 2) . "%" : "0%";
            $collectData['profitAmount'] = bcsub($collectData['totalPrice'], $collectData['totalCostPrice'], 2);
            $collectData['averageRetailPrice'] = $collectData['totalSaleQuantity'] > 0 ? bcdiv($collectData['totalPrice'], $collectData['totalSaleQuantity'], 2) : 0.00;
            $collectData['averageCostPrice'] = $collectData['totalSaleQuantity'] > 0 ? bcdiv($collectData['totalCostPrice'], $collectData['totalSaleQuantity'], 2) : 0.00;
            $collectData['grossProfitRate'] = $collectData['totalPrice'] > 0 ? bcmul(bcdiv($collectData['profitAmount'], $collectData['totalPrice'], 4), 100, 2) . "%" : "0%";

            unset($collectData['totalQuantity'], $collectData['g_id']);
            return $collectData;
        });
        return $this->r(200, $this->lang("query_success"), $collectList);
    }

    /**
     * 导出销售数据
     * @param $where
     * @return array|\think\response\Json
     */
    public function exportSaleDataCollect($where)
    {
        $field = "
        sod.g_id,so.machine_id,so.machine_name,sod.sku,sod.g_name,
        IFNULL(sum(sod.quantity - sod.refund_quantity),0) totalQuantity,
        IFNULL(sum(sod.total_sod_price - sod.refund_amount),0) totalPrice,
        IFNULL(sum(sod.total_sod_price),0) totalSalePrice,
        IFNULL(sum(sod.discount_price),0) totalDiscountPrice,
        IFNULL(sum(case sod.is_gift WHEN 1 THEN sod.quantity ELSE 0 END),0) totalGift,
        IFNULL(sum(sod.cost_price * (sod.quantity - sod.refund_quantity)),0) totalCostPrice
        ";
        $list = $this->getSaleOrdersDetailsJoinOrderList($where, 0, $field, 'totalPrice desc', 'm_id,g_id');
        actionLog($this->getLS(), '【SQL】获取导出数据');
        if ($list) {
            $list = $list->toArray();
            actionLog($list, '导出数据');
            foreach ($list as $k => $collectData) {
                $collectData['totalSaleQuantity'] = bcsub($collectData['totalQuantity'], $collectData['totalGift']);
                $collectData['totalClick'] = $this->getGoodsHitCount(['g_id' => $collectData['g_id']]) ?? 0;
                $collectData['clickConversionRate'] = $collectData['totalClick'] > 0 ? bcmul(bcdiv($collectData['totalSaleQuantity'], $collectData['totalClick'], 4), 100, 2) . "%" : "0%";
                $collectData['profitAmount'] = bcsub($collectData['totalPrice'], $collectData['totalCostPrice'], 2);
                $collectData['averageRetailPrice'] = $collectData['totalSaleQuantity'] > 0 ? bcdiv($collectData['totalPrice'], $collectData['totalSaleQuantity'], 2) : 0.00;
                $collectData['averageCostPrice'] = $collectData['totalSaleQuantity'] > 0 ? bcdiv($collectData['totalCostPrice'], $collectData['totalSaleQuantity'], 2) : 0.00;
                $collectData['grossProfitRate'] = $collectData['totalPrice'] > 0 ? bcmul(bcdiv($collectData['profitAmount'], $collectData['totalPrice'], 4), 100, 2) . "%" : "0%";
                $list[$k] = $collectData;
            };
            $title = [
                "machine_id" => "设备编号",
                "machine_name" => "设备名称",
                "sku" => "商品SKU",
                "g_name" => "商品名称",
                "totalSaleQuantity" => "实际销售量",
                "totalPrice" => "实际销售额",
                "totalDiscountPrice" => "总优惠额",
                "totalGift" => "赠品数量",
                "totalCostPrice" => "总成本",
                "totalClick" => "点击次数",
                "clickConversionRate" => "点击转化率",
                "profitAmount" => "利润额",
                "averageRetailPrice" => "平均售价",
                "averageCostPrice" => "平均成本价",
                "grossProfitRate" => "毛利率",
//                "totalSalePrice" =>       "销售总额",
//                "totalRefund" =>          "退款总额",
            ];
            $filename = "销售数据-" . date("YmdHis");
            return $this->sendToExport("运营数据-销售数据", $filename, $title, $list);
        }
        return $this->r(100, $this->lang("query_fail"));
    }
    
    /**
     * 异常订单列表（左连接异常处理表）
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param bool $supplier
     * @return array|string
     */
    public function getExceptionSoList($where, $pageNum = 0, $field = "*", $order = "", $supplier = false)
    {
        try {
            $this->autoInitExceptionPendingOrders();
            if ($supplier) {
                if ($this->manager['pid'] > 0) {
                    $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
                    if ($mIds) $where[] = ['a.m_id', 'in', $mIds];
                }
            }

            $data = $this->getSaleOrdersExceptionList($where, $pageNum, $field, $order ?: 'a.order_id desc')->toArray();
            return $this->r(200, $this->lang("query_success"), $data);
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 功能上线历史数据补录：把历史未处理异常订单批量写入异常处理表
     * @return bool
     */
    protected function autoInitExceptionPendingOrders()
    {
        try {
            $deadlineTs = 1776149111;
            $rows = Db::name('sale_orders')->alias('a')
                ->leftJoin('sale_orders_exception se', 'se.order_id = a.order_id')
                ->whereNull('se.id')
                ->whereIn('a.pay_status', ['3', '7'])
                ->whereIn('a.out_status', ['2', '5', '6'])
                ->where('a.pay_time', '<', $deadlineTs)
                ->field('a.order_id,a.m_id')
                ->select()
                ->toArray();

            if (!$rows) {
                return true;
            }

            $now = time();
            $insertData = [];
            foreach ($rows as $row) {
                $insertData[] = [
                    'order_id' => $row['order_id'],
                    'sod_id' => 0,
                    'm_id' => $row['m_id'] ?? 0,
                    'manager_id' => $this->manager['manager_id'] ?? 0,
                    'remark' => '系统自动补录：功能上线前未处理异常订单',
                    'status' => 1,
                    'create_time' => $now,
                ];
            }

            if ($insertData) {
                Db::name('sale_orders_exception')->insertAll($insertData);
            }
            return true;
        } catch (\Exception $e) {
            actionException($e, 1);
            return false;
        }
    }

    /**
     * 导出异常订单
     * @param $where
     * @param bool $supplier
     * @return array|string
     */
    public function exportExceptionSo($where, $supplier = false)
    {
        try {
            $aPayTypeCase = $this->buildPayTypeCaseSql('a.pay_type');
            if ($supplier) {
                if ($this->manager['pid'] > 0) {
                    $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
                    if ($mIds) $where[] = ['a.m_id', 'in', $mIds];
                }
            }

            $field = "a.order_id,a.machine_id,a.machine_name,a.trade_no,a.mch_no,a.total_quantity,(a.total_price - a.refund_amount) total_price,a.discount_price,a.retail_price,a.factory,a.inventory_location,se.remark exception_remark,se.create_time exception_create_time,
            (CASE a.order_type 
                WHEN 1 THEN '普通订单'
                WHEN 2 THEN '优惠券订单'
                WHEN 3 THEN '取货码订单'
                WHEN 4 THEN '盲盒活动'
                WHEN 5 THEN '满减满送活动'
                WHEN 6 THEN '叠加营销活动'
                END
            ) order_type,
            IFNULL(NULLIF(a.pay_channel_name,''),(CASE a.pay_channel
                WHEN 1 THEN '微程小程序订单'
                WHEN 2 THEN '机械车小程序订单'
                WHEN 3 THEN '售卖机会员积分订单'
                WHEN 4 THEN '商场积分订单'
                WHEN 5 THEN '取货码订单'
                WHEN 6 THEN '余额支付订单'
                WHEN 7 THEN '微信支付'
                WHEN 8 THEN '支付宝支付'
                WHEN 9 THEN 'POS/刷卡支付'
                WHEN 10 THEN '现金支付'
                WHEN 11 THEN '其他'
                ELSE '其他' END)) pay_channel,
            (CASE a.out_status
                WHEN 1 THEN '正常'
                WHEN 2 THEN '已发出货命令'
                WHEN 3 THEN '设备已接收'
                WHEN 4 THEN (CASE a.refund_status WHEN 1 THEN '正常' WHEN 2 THEN '已退款' WHEN 3 THEN '退款失败' END)
                WHEN 5 THEN '出货失败'
                WHEN 6 THEN '未取商品'
                END
            ) refund_status,
            {$aPayTypeCase} pay_type,
            FROM_UNIXTIME(a.pay_time,'%Y-%m-%d %H:%i:%s') pay_time,
            FROM_UNIXTIME(a.out_time,'%Y-%m-%d %H:%i:%s') out_time,
            (CASE se.status WHEN 1 THEN '已处理' ELSE '未处理' END) deal_status,
            (CASE se.status WHEN  1 THEN IFNULL(am.nickname, am.account) ELSE '' END) deal_manager";
            $list = $this->getSaleOrdersExceptionList($where, 0, $field, 'a.order_id desc')->toArray();
            if ($list) {
                $title = [
                    'order_id' => '订单ID',
                    'machine_id' => '设备编号',
                    'machine_name' => '设备名称',
                    'trade_no' => '订单编号',
                    'mch_no' => '支付编号',
                    'total_quantity' => '订单总数',
                    'total_price' => '支付金额',
                    'discount_price' => '优惠金额',
                    'retail_price' => '原订单总额',
                    'factory' => '所属工厂',
                    'inventory_location' => '库存地点',
                    'refund_status' => '订单状态',
                    'order_type' => '订单类型',
                    'pay_channel' => '订单分类',
                    'pay_type' => '支付类型',
                    'pay_time' => '支付时间',
                    'out_time' => '出货时间',
                    'exception_remark' => '处理备注',
                    'deal_status' => '处理状态',
                    'deal_manager' => '处理人',
                    'exception_create_time' => '处理时间',
                ];
                $filename = '异常订单列表-' . date('YmdHis');
                return $this->sendToExport('订单管理-销售订单', $filename, $title, $list);
            }
            return $this->rFail("暂无数据可导出");
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function getRemoteRecycleSodDetail($where)
    {
        $field = "sod_id,order_id,sku,g_name,channel_code,remote_refund_status,quantity,success_quantity,refund_quantity";
        $sale_order_field = "order_id,machine_id,machine_name,trade_no,out_status,order_type,pay_type,pay_method,pay_time,out_time";
        $sale_order_detail = $this->getSaleOrdersDetailsFind($where, $field)->toArray();
        $sale_order_data = $this->getSaleOrdersFind(['order_id' => $sale_order_detail['order_id']], $sale_order_field)->toArray();
        $rtn = array_merge($sale_order_detail, $sale_order_data);
        return $this->rQ($rtn);
    }

    /**
     * 异常订单处理
     * @param array $postData
     * @return array|string
     */
    public function exceptionHandle($postData = [])
    {
        try {
            $order = $this->getSaleOrdersFind(['order_id' => $postData['order_id']], 'order_id,m_id');
            if (!$order) return $this->r(100, $this->lang("VSaleOrders.order_no_data"));

            $insert = [
                'order_id' => $postData['order_id'],
                'sod_id' => 0,//目前没有关联具体子订单详情，后续如果有需要再修改
                'm_id' => $order['m_id'],
                'manager_id' => $this->manager['manager_id'],
                'remark' => $postData['remark'],
                'status' => 1,
                'create_time' => time(),
            ];

            $exist = Db::name('sale_orders_exception')->where(['order_id' => $postData['order_id']])->order('id desc')->find();
            if ($exist) {
                $result = Db::name('sale_orders_exception')->where(['id' => $exist['id']])->update($insert);
            } else {
                $result = Db::name('sale_orders_exception')->insert($insert);
            }

            if ($result === false) return $this->rFail('处理失败');
            return $this->rSuccess('处理成功');
        } catch (\Exception $e) {
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }
}
