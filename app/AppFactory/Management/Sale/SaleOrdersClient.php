<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/5
 * Time: 14:26
 */

namespace app\AppFactory\Management\Sale;


use app\AppFactory\Kernel\Support\Excel;
use app\AppFactory\Kernel\Traits\Auth\AuthManagerTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsHitTrait;
use app\AppFactory\Kernel\Traits\Payment\AfterOrderRefundTrait;
use app\AppFactory\Kernel\Traits\Payment\AliPayTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderPaymentTrait;
use app\AppFactory\Kernel\Traits\Payment\BeforeOrderRefundTrait;
use app\AppFactory\Kernel\Traits\Payment\JdCashierTrait;
use app\AppFactory\Kernel\Traits\Payment\WxPayTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersDailyCountTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRefundTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersRevenueTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyIncomeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyManagerTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\User\UserTrait;
use app\AppFactory\Management\ManagementClient;

class SaleOrdersClient extends ManagementClient
{
    use AuthManagerTrait;
    use SaleOrdersTrait, SaleOrdersRefundTrait, SaleOrdersRevenueTrait, SaleOrdersDailyCountTrait;
    use BeforeOrderPaymentTrait;
    use StrategyMachineTrait;
    use StrategyIncomeTrait;
    use StrategyManagerTrait;
    use UserTrait;
    use StrategyPayeeTrait;
    use BeforeOrderRefundTrait, AfterOrderRefundTrait;
    use WxPayTrait, AliPayTrait, JdCashierTrait;
    use GoodsHitTrait;

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
        "2" => "aliRefund",
        "3" => "tlRefund",
        "4" => "jdRefund",
    ];

    protected $postData;
    protected $totalRefundMoney;


    /**
     * 发起订单退款
     * @param $postData
     * @return array|bool|string
     */
    public function refundOrder($postData)
    {
        $this->order = $this->getSaleOrdersFind(['order_id' => $postData['order_id']]);
        if (!$this->order) return $this->rFail("查无订单数据");
        $this->order = $this->order->toArray();
        $check = $this->getSPayee();
//        if ($check !== true) {
//            return $check;
//        }
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
        $strategyPayee = $this->getStrategyPayeeContent($where, 'sp.*');
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
            if ($this->order['pay_type'] == 3 || $this->order['pay_type'] == 2) {
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
            $flag[] = $this->refundFail();
        }
        if ($this->order['pay_type'] == 2 || $this->order['pay_type'] == 3) {
            $flag[] = $this->refundFail();
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
        ];
        $whereToday = $where;
        $whereToday[] = ['create_date', '=', strtotime(date("Y-m-d"))];
        $today = $this->getSaleOrdersFind($whereToday, 'sum(total_price) saleMoney,sum(total_quantity) saleQuantity,sum(discount_price) discountMoney', '', 'create_date');
        if ($today) $today = $today->toArray();

        $whereYesterday = $where;
        $whereYesterday[] = ['create_date', '=', strtotime(date("Y-m-d 00:00:00", strtotime("-1 days")))];
        $yesterday = $this->getSaleOrdersFind($whereYesterday, 'sum(total_price) saleMoney,sum(total_quantity) saleQuantity,sum(discount_price) discountMoney', '', 'create_date');
        if ($yesterday) $yesterday = $yesterday->toArray();
        if ($today) $data['today'] = $today;
        if ($yesterday) $data['yesterday'] = $yesterday;
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
        if ($type == 1) {
            $field = "totalPrice,totalQuantity,countDate";
            $group = "";
            $where[] = ['create_date', '>=', strtotime("-1 months")];
        }
        if ($type == 2) {
            $field = "sum(totalPrice) totalPrice, sum(totalQuantity) totalQuantity, DATE_FORMAT(countDate,'Week %v,%x') week";
            $group = "week";
            $where[] = ['create_date', '>=', strtotime("-15 week")];
        }
        if ($type == 3) {
            $field = "sum(totalPrice) totalPrice, sum(totalQuantity) totalQuantity, DATE_FORMAT(countDate,'%x-%m') month";
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
        $list = $this->getSaleOrdersList($where, 0,
            'machine_id,machine_name,trade_no,mch_no,
                (discount_price + total_price) goods_total_price,discount_price,total_quantity,total_price,
                pay_code,
                FROM_UNIXTIME(pay_time,"%Y-%d-%m %H:%i:%s") pay_time,
                (CASE pay_type WHEN 1 THEN "微信支付" WHEN 2 THEN "支付宝支付" WHEN 3 THEN "" WHEN 4 THEN "京东收银" WHEN 0 THEN "免支付" END) pay_type,
                (CASE pay_method 
                    WHEN 1 THEN "免支付" 
                    WHEN 11 THEN "付款码支付" 
                    WHEN 12 THEN "JSAPI支付" 
                    WHEN 13 THEN "小程序支付" 
                    WHEN 14 THEN "Native支付" 
                    WHEN 15 THEN "刷脸支付"
                    WHEN 21 THEN "手机网站支付"
                    WHEN 22 THEN "当面付（付款码）"
                    WHEN 23 THEN "当面付（扫码支付）"
                    WHEN 31 THEN "扫码支付"
                    WHEN 32 THEN "反扫支付"
                    WHEN 41 THEN "扫码支付"
                    WHEN 42 THEN "刷卡支付（被扫支付）"
                END) pay_method
                '
        );
        if ($list) {
            $list = $list->toArray();
            $title = [
                "machine_id" => "设备编号",
                "machine_name" => "设备名称",
                "trade_no" => "订单编号",
                "mch_no" => "交易编号",
                "goods_total_price" => "商品总价",
                "discount_price" => "优惠金额",
                "total_quantity" => "总数量",
                "total_price" => "实际支付金额",
                "pay_code" => "支付操作码（付款码/支付二维码/提货码）",
                "pay_time" => "支付时间",
                "pay_type" => "支付类型",
                "pay_method" => "支付方式",
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
        $field = "so.machine_id,so.machine_name,so.trade_no,sod.batch_number,sod.sku,sod.g_name,sod.channel_code,sod.retail_price,sod.discount_price,sod.total_sod_price,
            (CASE so.out_status WHEN 2 THEN '已发出货命令' WHEN 3 THEN '等待出货结果' WHEN 4 THEN '出货成功' WHEN 5 THEN '出货失败' END) out_status,
            (CASE so.order_type WHEN 1 THEN '普通订单' WHEN 2 THEN '优惠券订单' WHEN 3 THEN '取货码订单' WHEN 4 THEN '付费抽奖活动' WHEN 5 THEN '满减满送活动' END) order_type,
            sod.deliver_pics,
            (CASE so.pay_type WHEN 0 THEN '免支付' WHEN 1 THEN '微信支付' WHEN 2 THEN '支付宝支付' WHEN 3 THEN '' WHEN 4 THEN '京东收银' ELSE '' END) pay_type,
            (CASE so.pay_method 
            WHEN 1 THEN '免支付' 
            WHEN 14 THEN 'Native支付' 
            WHEN 23 THEN '扫码支付' 
            WHEN 41 THEN '扫码支付' WHEN 42 THEN '被扫支付'
            ELSE '' END) pay_method,
            FROM_UNIXTIME(so.create_time,'%Y-%m-%d %H:%i:%s') create_time,
            FROM_UNIXTIME(so.pay_time,'%Y-%m-%d %H:%i:%s') pay_time,
            pay_code";
        $list = $this->getSaleOrdersDetailsJoinOrderList($where, 0, $field, "so.trade_no");
        if ($list) {
            $list = $list->toArray();
            if ($list) {
                $title = [
                    "machine_id" => "设备编号",
                    "machine_name" => "设备名称",
                    "trade_no" => "交易号",
                    "batch_number" => "商品序列号",
                    "sku" => "SKU",
                    "g_name" => "SKU名称",
                    "channel_code" => "槽位号",
                    "retail_price" => "单价",
                    "discount_price" => "优惠价",
                    "total_sod_price" => "实收金额",
                    "out_status" => "状态",
                    "order_type" => "订单类型",
                    "deliver_pics" => "出货图像",
                    "pay_type" => "支付类型",
                    "pay_method" => "支付方式",
                    "create_time" => "交易时间",
                    "pay_time" => "支付时间",
                    "pay_code" => "支付操作码（用户付款码/支付二维码/提货码/优惠码）",
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
        $field = "sor.machine_id,sor.machine_name,sor.trade_no,
                sod.batch_number,sod.sku,sod.g_name,sod.channel_code,sod.retail_price,sod.discount_price,sod.total_sod_price,
                (CASE so.out_status WHEN 2 THEN '已发出货命令' WHEN 3 THEN '等待出货结果' WHEN 4 THEN '出货成功' WHEN 5 THEN '出货失败' END) out_status,
                (CASE so.order_type WHEN 1 THEN '普通订单' WHEN 2 THEN '优惠券订单' WHEN 3 THEN '取货码订单' WHEN 4 THEN '付费抽奖活动' WHEN 5 THEN '满减满送活动' END) order_type,
                sod.deliver_pics,
                (CASE so.pay_type WHEN 0 THEN '免支付' WHEN 1 THEN '微信支付' WHEN 2 THEN '支付宝支付' WHEN 3 THEN '' WHEN 4 THEN '京东收银' ELSE '' END) pay_type,
                (CASE so.pay_method 
                WHEN 1 THEN '免支付' 
                WHEN 14 THEN 'Native支付' 
                WHEN 23 THEN '扫码支付' 
                WHEN 41 THEN '扫码支付' WHEN 42 THEN '被扫支付'
                ELSE '' END) pay_method,
                FROM_UNIXTIME(so.create_time,'%Y-%m-%d %H:%i:%s') create_time,
                FROM_UNIXTIME(so.pay_time,'%Y-%m-%d %H:%i:%s') pay_time,
                so.pay_code,
                sor.refund_trade_no,
                sor.refund_no,
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
                "trade_no" => "交易号",
                "batch_number" => "商品序列号",
                "sku" => "SKU",
                "g_name" => "SKU名称",
                "channel_code" => "槽位号",
                "retail_price" => "单价",
                "discount_price" => "优惠价",
                "total_sod_price" => "实收金额",
                "pay_type" => "支付类型",
                "pay_method" => "支付方式",
                "pay_time" => "支付时间",
                "create_time" => "交易时间",
                "deliver_pics" => "照片",
                "order_type" => "订单类型",
                "pay_code" => "支付操作码（用户付款码/支付二维码/提货码/优惠码）",
                "out_status" => "出货状态",
                "refund_trade_no" => "退款编号",
                "refund_no" => "平台退款编号",
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
     * @return array|\think\response\Json
     */
    public function getTotalReport($where, $field = "*", $order = "")
    {
        $data = $this->getSaleOrdersDailyCountFind($where, $field, $order);
        return $this->rQ($data);
    }

    /**
     * 销售报表
     * @param $where
     * @param $pageNum
     * @param string $order
     * @return array|\think\response\Json
     */
    public function getReportList($where, $pageNum, $order = "")
    {
        $field = "countDate,
        SUM(totalPrice) totalPrice,
        SUM(lotteryAmount) lotteryAmount,
        sum(totalRefundAmount) totalRefundAmount,
        SUM(totalPrice - totalDiscountPrice - totalRefundAmount) totalSalePrice,
        SUM(totalQuantity - totalRefundQuantity) totalSaleQuantity,
        SUM(order_num) order_num,
        SUM(totalDiscountPrice) totalDiscountPrice,
        SUM(giftQuantity) giftQuantity";
        $group = "create_date";
        return $this->rQ($this->getSaleOrdersDailyCountList($where, $pageNum, $field, $order, $group));
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
        SUM(totalPrice - totalDiscountPrice - totalRefundAmount) totalSalePrice,
        SUM(totalQuantity - totalRefundQuantity) totalSaleQuantity,
        SUM(order_num) order_num,
        SUM(totalDiscountPrice) totalDiscountPrice,
        SUM(giftQuantity) giftQuantity";
        $group = "create_date";
        $list = $this->getSaleOrdersDailyCountList($where, 0, $field, $order, $group);
        if ($list) {
            $list = $list->toArray();
            $title = [
                "countDate" => "日",
                "totalPrice" => "设备销售额",
                "lotteryAmount" => "抽奖销售额",
                "totalRefundAmount" => "退款金额",
                "totalSalePrice" => "实际销售金额",
                "totalSaleQuantity" => "实际销售量",
                "order_num" => "订单总数",
                "totalDiscountPrice" => "总优惠额",
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
        IFNULL(sum(so.total_quantity - so.refund_quantity),0) totalQuantity,
        IFNULL(sum(so.total_price - so.refund_amount),0) totalPrice,
        IFNULL(sum(so.discount_price),0) totalDiscountPrice,
        IFNULL(sum(so.total_price),0) totalSalePrice,
        IFNULL(sum(case sod.is_gift WHEN 1 THEN sod.quantity ELSE 0 END),0) totalGift,
        IFNULL(sum(so.cost_price),0) totalCostPrice
        ";
        $collectData = $this->getSaleOrdersDetailsData($whereCollect,$field)->toArray();
        $collectData['totalSaleQuantity'] = bcsub($collectData['totalQuantity'],$collectData['totalGift']);
        $whereGIds = $where;
        $whereGIds[] = ['g_id',">",0];
        $gIds = $this->joinSoSodColumn($whereGIds,'g_id','g_id');
        $collectData['totalClick'] = $this->getGoodsHitCount(['g_id' => $gIds]) ?? 0;
        $collectData['clickConversionRate'] = $collectData['totalClick'] > 0 ? bcmul(bcdiv($collectData['totalSaleQuantity'],$collectData['totalClick'],4),100,2) . "%" : "0%";
        $collectData['profitAmount'] = bcsub($collectData['totalPrice'],$collectData['totalCostPrice'],2);
        $collectData['averageRetailPrice'] = $collectData['totalSaleQuantity'] > 0 ? bcdiv($collectData['totalCostPrice'],$collectData['totalSaleQuantity'], 2) : 0.00;
        $collectData['averageCostPrice'] = $collectData['totalSaleQuantity'] > 0 ? bcdiv($collectData['totalCostPrice'], $collectData['totalSaleQuantity'],2) : 0.00;
        $collectData['grossProfitRate'] = $collectData['totalPrice'] > 0 ?  bcmul(bcdiv($collectData['profitAmount'],$collectData['totalSalePrice'],4),100,2) . "%"  : "0%";
        unset($collectData['totalQuantity']);
        return $this->r(200,$this->lang("query_success"),$collectData);
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
     * @param int $pageNum      页面数据条数
     * @return array|\think\response\Json
     */
    public function saleDataCollectList($where,$pageNum = 0)
    {
        $field = "
        sod.g_id,so.machine_id,so.machine_name,sod.sku,sod.g_name,
        IFNULL(sum(so.total_quantity - so.refund_quantity),0) totalQuantity,
        IFNULL(sum(so.total_price - so.refund_amount),0) totalPrice,
        IFNULL(sum(so.total_price),0) totalSalePrice,
        IFNULL(sum(so.discount_price),0) totalDiscountPrice,
        IFNULL(sum(case sod.is_gift WHEN 1 THEN sod.quantity ELSE 0 END),0) totalGift,
        IFNULL(sum(so.cost_price),0) totalCostPrice
        ";
        $collectList = $this->getSaleOrdersDetailsJoinOrderList($where,$pageNum,$field,'totalPrice desc','m_id,g_id')->each(function ($collectData) {
            $collectData['totalSaleQuantity'] = bcsub($collectData['totalQuantity'],$collectData['totalGift']);
            $collectData['totalClick'] = $this->getGoodsHitCount(['g_id' => $collectData['g_id']]) ?? 0;
            $collectData['clickConversionRate'] = $collectData['totalClick'] > 0 ? bcmul(bcdiv($collectData['totalSaleQuantity'],$collectData['totalClick'],4),100,2) . "%" : "0%";
            $collectData['profitAmount'] = bcsub($collectData['totalPrice'],$collectData['totalCostPrice'],2);
            $collectData['averageRetailPrice'] = $collectData['totalSaleQuantity'] > 0 ? bcdiv($collectData['totalCostPrice'],$collectData['totalSaleQuantity'], 2) : 0.00;
            $collectData['averageCostPrice'] = $collectData['totalSaleQuantity'] > 0 ? bcdiv($collectData['totalCostPrice'], $collectData['totalSaleQuantity'],2) : 0.00;
            $collectData['grossProfitRate'] = $collectData['totalPrice'] > 0 ?  bcmul(bcdiv($collectData['profitAmount'],$collectData['totalSalePrice'],4),100,2) . "%"  : "0%";

            unset($collectData['totalQuantity'],$collectData['g_id']);
            return $collectData;
        });
        return $this->r(200,$this->lang("query_success"),$collectList);
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
        IFNULL(sum(so.total_quantity - so.refund_quantity),0) totalQuantity,
        IFNULL(sum(so.total_price - so.refund_amount),0) totalPrice,
        IFNULL(sum(so.refund_amount),0) totalRefund,
        IFNULL(sum(so.total_price),0) totalSalePrice,
        IFNULL(sum(so.discount_price),0) totalDiscountPrice,
        IFNULL(sum(case sod.is_gift WHEN 1 THEN sod.quantity ELSE 0 END),0) totalGift,
        IFNULL(sum(so.cost_price),0) totalCostPrice
        ";
        $list = $this->getSaleOrdersDetailsJoinOrderList($where,0,$field,'totalPrice desc','m_id,g_id');
        if ($list) {
            $list = $list->toArray();
            foreach($list as $k => $collectData) {
                $collectData['totalSaleQuantity'] = bcsub($collectData['totalQuantity'],$collectData['totalGift']);
                $collectData['totalClick'] = $this->getGoodsHitCount(['g_id' => $collectData['g_id']]) ?? 0;
                $collectData['clickConversionRate'] = $collectData['totalClick'] > 0 ? bcmul(bcdiv($collectData['totalSaleQuantity'],$collectData['totalClick'],4),100,2) . "%" : "0%";
                $collectData['profitAmount'] = bcsub($collectData['totalPrice'],$collectData['totalCostPrice'],2);
                $collectData['averageRetailPrice'] = $collectData['totalSaleQuantity'] > 0 ? bcdiv($collectData['totalCostPrice'],$collectData['totalSaleQuantity'], 2) : 0.00;
                $collectData['averageCostPrice'] = $collectData['totalSaleQuantity'] > 0 ? bcdiv($collectData['totalCostPrice'], $collectData['totalSaleQuantity'],2) : 0.00;
                $collectData['grossProfitRate'] = $collectData['totalPrice'] > 0 ?  bcmul(bcdiv($collectData['profitAmount'],$collectData['totalPrice'],4),100,2) . "%"  : "0%";
                unset($collectData['totalQuantity'],$collectData['g_id']);
                $list[$k] = $collectData;
            };
            $title = [
                "machine_id" =>           "设备编号",
                "machine_name" =>         "设备名称",
                "sku" =>                  "商品SKU",
                "g_name" =>               "商品名称",
                "totalClick" =>           "点击次数",
                "clickConversionRate" =>  "点击转化率",
                "totalSaleQuantity" =>    "实际销售量",
                "totalPrice" =>           "实际销售额",
                "totalSalePrice" =>       "销售总额",
                "totalRefund" =>          "退款总额",
                "totalDiscountPrice" =>   "总优惠额",
                "totalGift" =>            "赠品数量",
                "totalCostPrice" =>       "总成本",
                "profitAmount" =>         "利润额",
                "averageRetailPrice" =>   "平均售价",
                "averageCostPrice" =>     "平均成本价",
                "grossProfitRate" =>      "毛利率",
            ];
            $filename = "销售数据-" . date("YmdHis");
            return $this->sendToExport("运营数据-销售数据",$filename,$title,$list);
        }
        return $this->r(100,$this->lang("query_fail"));
    }
}