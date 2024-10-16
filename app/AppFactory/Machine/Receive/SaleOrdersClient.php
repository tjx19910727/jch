<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/22
 * Time: 15:27
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Traits\Machine\MachineOnlineDetailsTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersUnclaimedTrait;
use app\AppFactory\Machine\MachineBaseClient;

class SaleOrdersClient extends MachineBaseClient
{
    use SaleOrdersUnclaimedTrait, SaleOrdersTrait;
    use MachineOnlineDetailsTrait;

    protected $message = [];

    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        $this->data = json2arr($this->config['data']);
        $this->machine['last_online_time'] = time();
        $this->machine['online'] = 1;
        if (!isset($this->data['msgType']) || (isset($this->data['msgType']) && $this->data['msgType'] != "heartbeat")) {
            $this->heartbeat();
        }
        $this->newRecord();
    }

    /**
     * 上报未取事件
     * @return array|bool|string
     */
    public function subUnclaimed()
    {
        $order = $this->getSaleOrdersFind(['order_id' => $this->data['order_id']],'order_id,trade_no,m_id,machine_id,machine_name,pay_time,ao_id');
        if (!$order) return $this->r(100, $this->lang("VSaleOrders.order_no_data"));
        $detailsList = json2arr($this->data['details']);
        if (!$detailsList) return $this->r(100, $this->lang("VSaleOrders.detailsList_no_data"));
        $this->startTrans();
        try {
            foreach ($detailsList as $key => $value) {
                $d = $this->getSaleOrdersDetailsFind(['sod_id' => $value['sod_id']], 'sod_id,mc_id,channel_code,mg_id,g_id,g_name,sku,retail_price');
                if (!$d) {
                    $this->rollbackTrans();
                    return $this->r(100, $this->lang("VSaleOrders.sod_no_data"));
                }
                $updateSod['sod_id'] = $value['sod_id'];
                $updateSod['deliver_pics'] = $value['deliver_pics'];
                $updateSod['out_sequence'] = $value['out_sequence'];
                $flag[] = $this->updateSaleOrdersDetails($updateSod);
                $insert = [
                    "order_id" => $order['order_id'],
                    "trade_no" => $order['trade_no'],
                    "sod_id" => $d['sod_id'],
                    "m_id" => $order['m_id'],
                    "machine_id" => $order['machine_id'],
                    "machine_name" => $order['machine_name'],
                    "mc_id" => $d['mc_id'],
                    "channel_code" => $d['channel_code'],
                    "mg_id" => $d['mg_id'],
                    "g_id" => $d['g_id'],
                    "g_name" => $d['g_name'],
                    "sku" => $d['sku'],
                    "retail_price" => $d['retail_price'],
                    "is_match" => $value['is_match'],
                    "is_claim" => $value['is_claim'],
                    "is_out" => $value['is_out'],
                    "is_close" => $value['is_close'],
                    "out_sequence" => $value["out_sequence"] ?? 1,
                    "quantity" => $value['quantity'],
                    "duration" => $value['duration'],
                    "deliver_pics" => $value['deliver_pics'],
                    "transfer_time" => $order['pay_time'],
                    "ao_id" => $order['ao_id'],
                ];
                $flag[] = $this->addSaleOrdersUnclaimed($insert);
                $flag[] = $this->setMachineIncField(['m_id' => $order['m_id']], 'recycle_bin_stock', $value['quantity']);

                if ($value['is_claim'] == 2) {
                    // 出货失败发送通知
                    try {
                        $this->noticeSendData = [
                            "ao_id" => $this->machine['ao_id'],
                            "m_id" => $this->machine['m_id'],
                            "templateType" => "tException",
                            "replaceData" => [
                                "machine_id" => $this->machine['machine_id'],
                                "exceptionDeclaration" => $order['order_id'] . "_" . $d['sod_id'] . $this->lang("tException.unclaimed")
                            ]
                        ];
                        actionLog($this->noticeSendData, '发送通知');
                        $result = @$this->noticeSend();
                        actionLog($result, '发送结果');
                    } catch (\Exception $e) {
                        actionLog("发送交易异常抛出异常");
                        actionException($e, 1);
                    }
                }
            }
            $result = $this->checkFlag($flag);
            return $this->checkTrans($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }
}