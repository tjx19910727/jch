<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/7/3
 * Time: 8:17
 */

namespace app\AppFactory\Kernel\Traits\Payment;



use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Support\TencentCloud;
use app\AppFactory\Kernel\Support\Trip\Trip;
use app\AppFactory\RabbitMq\MqProducer;

trait AfterOrderPaymentTrait
{

    /**
     * 处理支付成功
     */

    /**
     * @var array 分润状态
     */
    protected $revenueStatus = [
        "1" => 2,         // 电子钱包
        "21" => 1,        // 微信企业付款至零钱
        "22" => 1,        // 微信商户转账至零钱
        "3" => 1,         // 支付宝转账至零钱
        "4" => 2,         // 京东收银分账
    ];

    /**
     * 支付成功
     * @return mixed
     */
    public function paymentSuccessful()
    {
        $flag = [];
        if ($this->order['machine_id']) {
            // 发送给设备终端支付成功状态
            $this->sendToMachine(['machine_id' => $this->order['machine_id']],'paySuccess',['trade_no' => $this->order['trade_no']]);
        }
        $this->order['pay_status'] = 3;
        $this->order['pay_time'] = time();
        if ($this->order['order_type'] != 4) {
            $this->outGoods();
        }
        $this->handleHotel(1);
        actionLog($this->order,'订单数据');
        $flag[] = $this->updateSaleOrders($this->order);
        actionLog($this->getLS(),'订单修改数据');
        $result = flag_check($flag);
        return $result;
    }

    /**
     * 出货
     * @return string
     */
    protected function outGoods()
    {
        $details = $this->order['details'] ?? $this->getSaleOrdersDetailsList(['order_id' => $this->order['order_id']]);
        if ($details) {
            $contentArr = [];
            $outArr = [];
            // 旧版本数据，待软件更新后删除
            foreach ($details as $k => $v) {
                if ($v['g_type'] == 1) {
                    $dc = [
                        $v['channel_code'],
                        $v['quantity'],
                    ];
                    $contentArr[$v['channel_position']][] = $dc;
                }
            }
            // 新数据格式
            foreach ($details as $k => $v) {
                if ($v['g_type'] != 1 && isset($v['gmg_id']) && $v['gmg_id']) {
                    $flag[] = $this->setGoodsMultipleGoodsDec(['gmg_id' => $v['gmg_id']],'stock');
                    actionLog($this->getLS(),'减固定组合商品酒店库存');
                }
                if ($v['g_type'] == 1) {
                    $dc = [
                        "channel_code" => $v['channel_code'],
                        "quantity" => $v['quantity'],
                        "is_gift" => $v['is_gift'] ?? 2,
                    ];
                    $outArr[$v['channel_position']][] = $dc;
                }
                if ($v['g_type'] == 3) {
                    $updateSod['sod_id'] = $v['sod_id'];
                    // 获取核销码
                    $updateSod['checkOff_code'] = $this->getDetailsCheckOffCode();
                    $this->updateSaleOrdersDetails($updateSod);
                }
            }

            $msg_id = uniqid();
            $content = [
                "msgType" => "outGoods",
                "trade_no" => $this->order['trade_no'],
                "main" => $contentArr,
                "outGoods" => $outArr,
            ];
            if (isset($this->order['out_port']) && $this->order['out_port']) $content['outPort'] = $this->order['out_port'];
            $content = json_encode($content);
            $data = [
                "timestamp" => time(),
                "msg_id" => $msg_id,
                "machine_id" => $this->order['machine_id'],
                "data" => $content,
            ];
            $this->config['machine_id'] = $this->order['machine_id'];
            $data['sign'] = $this->makeSign($data);
            actionLog($data,'下发数据');

            // 生成发送记录
            $insertMqRecord = [
                "m_id" => $this->order['m_id'],
                "machine_id" => $data['machine_id'],
                "machine_name" => $this->order['machine_name'],
                "msg_id" => $msg_id,
                "path" => "outGoods",
                "content" => json_encode($data),
                "from" => 2,
                "type" => 2,
            ];
            $this->addMachineMqRecord($insertMqRecord);
            actionLog($this->getLS(),'生成发送记录');

            $result = MqProducer::dataSend($data,$data['machine_id']);
            actionLog($result,'下发数据结果');
            $this->order['out_status'] = 2;
            return $result;
        }
        return $this->r(100,$this->lang("VOutGoods.details_no_data"));
    }

    /**
     * 结算收益
     * @param string $status 分润成功时不能传参，分润失败传3
     * @return int
     */
    protected function settlementRevenue($status = '')
    {
        $flag[] = 1;
        $where['order_id'] = $this->order['order_id'];
        $revenue = $this->getSaleOrdersRevenueList($where);
        if ($revenue) {
            foreach ($revenue as $key => $value) {
                $update['sor_id'] = $value['sor_id'];
                $update['status'] = $status ? : $this->revenueStatus[$value['revenue_type']];
                // 已分润状态，增加分润时间
                if ($update['status'] == 2 || $update['status'] == 3) $update['revenue_time'] = time();
                // 电子钱包
                if ($value['revenue_type'] == 1) {
                    $result = $this->incAuthManager(['manager_id' => $value['manager_id']],'balance',$value['income_amount']);
                    actionLog($result,'增加账号余额结果');
                    actionLog($this->getLS(),'增加账号余额SQL');
                }
                $flag[] = $this->updateSaleOrdersRevenue($update);
                actionLog($this->getLS(),'结算收益SQL');
            }
            actionLog($flag,'结算收益flag');
        }
        return flag_check($flag);
    }

    private function sendNotice()
    {
        try {
            $this->noticeSendData = [
                "ao_id" => $this->machine['ao_id'],
                "m_id" => $this->machine['m_id'],
                "templateType" => "sale",
                "replaceData" => [
                    "machine_id" => $this->machine['machine_id'],
                    "machine_name" => $this->machine['machine_name'],
                    "trade_no" => $this->order['trade_no'],
                    "money" => number_format($this->order['total_price'],2,'.',','),
                ]
            ];
            $result = $this->noticeSend();
            actionLog($result, '发送结果');
        } catch (\Exception $e) {
            actionLog("发送库存预警抛出异常");
            actionException($e, 1);
        }
    }
    /**
     * 支付失败处理
     */

    /**
     * 支付失败
     * @return mixed
     */
    public function paymentFailed()
    {
        $this->order['pay_status'] = 4;
        $this->order['pay_time'] = time();
        $this->handleHotel(2);
        $this->sendToMachine(['machine_id' => $this->order['machine_id']],'payFail',['trade_no' => $this->order['trade_no']]);
        return $this->updateSaleOrders($this->order);
    }

    /**
     * 支付失败
     * @return mixed
     */
    public function paymentError()
    {
        $this->order['pay_status'] = 4;
        $this->order['pay_time'] = time();
        $this->handleHotel(2);
        $this->sendToMachine(['machine_id' => $this->order['machine_id']],'payError',['trade_no' => $this->order['trade_no']]);
        return $this->updateSaleOrders($this->order);
    }

    /**
     * 处理订单酒店数据
     * @param int $status 1：支付成功，2：支付失败
     * @return mixed
     */
    public function handleHotel($status)
    {
        // 有酒店数据
        if ($this->order['has_hotel'] == 1) {
            $sh = $this->getSaleHotelFind(['order_id' => $this->order['order_id']]);
            if ($sh) {
                $sh = $sh->toArray();
                actionLog($sh,'酒店数据');
                $updateSh['sh_id'] = $sh['sh_id'];
                $updateSh['pay_time'] = time();
                $updateSh['pay_status'] = ($status == 1 ? 3 : 4);
                $updateSh['create_status'] = 1;
                // 自营酒店
                if ($sh['hotelFrom'] == 2 && $status == 1) {
                    if ($sh['gmg_id']) {
                        $flag[] = $this->setGoodsMultipleGoodsDec(['gmg_id' => $sh['gmg_id']],'stock');
                        actionLog($this->getLS(),'减固定组合商品酒店库存');
                    }
//                    $updateSh['checkOff_code'] = $this->getHotelCheckOffCode();
//                    $updateSh['create_status'] = 2;
//                    $updateSh['reservation_status'] = 2;
//                    // 发送酒店预订通知
//                    $smsParam = [
//                        $updateSh['checkOff_code'],
//                    ];
//                    $phoneNumber = [
//                        $this->order['mobile'],
//                    ];
//                    $result = TencentCloud::sendSms($smsParam,$phoneNumber);
//                    actionLog($result,'预订酒店发送短信通知');
                }
                if ($sh['hotelFrom'] == 1 && $this->order['pay_type'] <> 5) {
                    $params = [
                        "tradeNo" => $this->order['out_trade_no'],
                        "payStatus" => $status,
                    ];
                    actionLog($params,'支付结果通知丽呈小程序');
                    $result = Trip::order()->payNotify($params);
                    $result = json2arr($result);
                    actionLog($result,'支付结果通知丽呈小程序结果');
                    if (isset($result['result']['orderStatus']) && $result['result']['orderStatus'] == 1) {
                        $updateSh['create_status'] = 1;
                    } else {
                        $updateSh['create_status'] = 2;
                    }
                }
                $updateResult = $this->updateSaleHotel($updateSh);
                actionLog($this->getLS(),'修改订单酒店数据');
                return $updateResult;
            }
        }
    }
}