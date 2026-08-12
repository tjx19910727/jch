<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/30
 * Time: 9:00
 */

namespace app\AppFactory\Machine\Receive;


use app\AppFactory\Kernel\ServiceContainer;
use app\AppFactory\Kernel\Support\Validate\Machine\VReport;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityCouponUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdContentTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityFdUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryConfigTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryContentTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryUsedGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityLotteryUsedTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickCodeTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityPickTrait;
use app\AppFactory\Kernel\Traits\Api\ApiAdvanceTrait;
use app\AppFactory\Kernel\Traits\Api\ApiCallbackTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCitiesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthCountriesTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthRegionsTrait;
use app\AppFactory\Kernel\Traits\Earth\EarthStatesTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsChangeTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsHitTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineConfigTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineErrorCodeTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineInfoTrait;
use app\AppFactory\Kernel\Traits\Machine\MachinePreReplenishmentTrait;
use app\AppFactory\Kernel\Traits\Machine\SimSignalLogTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineServiceLogTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineVersionPlanTrait;
use app\AppFactory\Kernel\Traits\Mq\OutGoodsTrait;
use app\AppFactory\Kernel\Traits\RemoteActionLog\RemoteActionLogTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyMachineTrait;
use app\AppFactory\Kernel\Traits\Strategy\StrategyPayeeTrait;

class MqClient extends ReceiveBaseClient
{
    use SaleOrdersTrait,OutGoodsTrait;
    use RemoteActionLogTrait;
    use MachineInfoTrait,MachineGoodsTrait,MachineChannelTrait,MachineVersionPlanTrait,MachineConfigTrait;
    use SimSignalLogTrait;
    use MachineErrorCodeTrait;
    use GoodsTrait,GoodsHitTrait,GoodsChangeTrait;
    use StrategyPayeeTrait,StrategyMachineTrait;
    use ApiAdvanceTrait,ApiCallbackTrait;
    use ActivityFdUsedTrait,ActivityFdTrait,ActivityFdContentTrait;
    use ActivityCouponTrait,ActivityCouponUsedTrait;
    use ActivityPickTrait,ActivityPickCodeTrait;
    use ActivityLotteryTrait,ActivityLotteryConfigTrait,ActivityLotteryContentTrait,ActivityLotteryUsedTrait,ActivityLotteryUsedGoodsTrait;
    use EarthCitiesTrait,EarthRegionsTrait,EarthCountriesTrait,EarthStatesTrait;
    use MachineServiceLogTrait;
    use MachinePreReplenishmentTrait;

    protected $order;
    public function __construct(ServiceContainer $app)
    {
        parent::__construct($app);
        if ($this->isSignKeyBootstrapHandled()) {
            return;
        }
        if (!isset($this->data['sign'])) {
            throw new \InvalidArgumentException('非认证MQ消息缺少签名');
        }
        if (isset($this->data['sign']) && $this->checkSign($this->data) !== true) {
            actionLog([
                'machine_id' => $this->data['machine_id'] ?? '',
                'msg_id' => $this->data['msg_id'] ?? '',
            ],'验签失败',"DataUpload");
            throw new \InvalidArgumentException('MQ消息验签失败');
        }
        $this->message = json2arr($this->data['data'] ?? "");
        actionLog($this->message, '消息数据', "DataUpload");
        if (!$this->message) {
            throw new \InvalidArgumentException('MQ message数据为空');
        }
        try {
            validate(VReport::class)->scene('onMessage')->check($this->data);
        } catch (\Exception $e) {
            actionLog($e->getMessage(), '数据格式错误', 'DataUpload');
            throw new \InvalidArgumentException('MQ顶层数据格式错误', 0, $e);
        }
        $this->dataRecord(2);
    }

    /**
     * 处理设备上报
     * msgType: outGoods、heartbeat、updateComplete、goodsHit、transactionVideo、img、channelImg、
     *          light、volume、errorCode、uploadInfo、machineCkcOnOff
    *          doorOpen、powerWakeUp、initialization、axisOffset、updateSimSignal
     * 远程退货动作组：remoteOutGoods、checkRecycleBox、pickUpDoorOpen、pickUpDoorClose、takePhotos、recycGoods
     * @return int
     */
    public function onMessage()
    {
        try {
            if ($this->isSignKeyBootstrapHandled()) {
                return true;
            }
            if ($this->message) {
                $func_name = $this->message['msgType'] ?? "";
                if (method_exists(self::class, $func_name)) {
                    try {
                        validate(VReport::class)->scene($this->message['msgType'])->check($this->message);
                    } catch (\Exception $e) {
                        actionLog($e->getMessage(), '数据格式错误', 'DataUpload');
                        throw new \InvalidArgumentException('数据格式错误', 0, $e);
                    }
                    // 历史处理方法返回值不统一，运输层只以是否抛出异常判断成败。
                    $this->$func_name();
                    return true;
                }
                actionLog($this->message,'没有对应的消息类型');
                throw new \InvalidArgumentException('没有对应的MQ消息类型');
            }
            throw new \InvalidArgumentException('MQ message数据为空');
        } catch (\Throwable $e) {
            actionException($e,1);
            throw $e;
        }
    }

    /**
     * 处理回收箱容量上报（设备对 checkRecycleBox 的响应）
     * 总容量始终以 machine_config.recycle_bin_capacity 为准。
     * @return int 0 成功, 1 失败
     */
    public function checkRecycleBox()
    {
        try {
            $config = $this->getMachineConfigFind(['m_id' => $this->machine['m_id']], 'recycle_bin_capacity');
            $totalCapacity = intval($config['recycle_bin_capacity'] ?? 0);
            if ($totalCapacity < 0) {
                $totalCapacity = 0;
            }
            $update = [
                'm_id' => $this->machine['m_id'],
                'recycle_box_total_capacity' => $totalCapacity,
            ];
            if (isset($this->message['recycle_box_remain_capacity'])) {
                $remainCapacity = (int)$this->message['recycle_box_remain_capacity'];
                if ($remainCapacity < 0) {
                    $remainCapacity = 0;
                }
                if ($remainCapacity > $totalCapacity) {
                    $remainCapacity = $totalCapacity;
                }
                $update['recycle_box_remain_capacity'] = $remainCapacity;
            }
            $result = $this->updateMachine($update);
            actionLog($this->getLS(), '【SQL】更新回收箱容量', 'checkRecycleBox');
            actionLog($result, '更新回收箱容量结果', 'checkRecycleBox');
            return 0;
        } catch (\Exception $e) {
            actionException($e,1);
            return 1;
        }
    }

    /**
     * 处理打开出料箱门回执。
     * @return int
     */
    public function pickUpDoorOpen()
    {
        return $this->updateRemoteActionLogStatus('pickUpDoorOpen');
    }

    /**
     * 处理关闭出料箱门回执。
     * @return int
     */
    public function pickUpDoorClose()
    {
        return $this->updateRemoteActionLogStatus('pickUpDoorClose');
    }

    /**
     * 根据设备回执更新远程动作日志状态。
     * 优先按 log_id 更新；旧设备未回传 log_id 时，回退到该设备该动作最近一条待处理日志。
     * @param string $msgType
     * @return int
     */
    protected function updateRemoteActionLogStatus($msgType)
    {
        try {
            $status = intval($this->message['status'] ?? 3);
            if (!in_array($status, [2, 3, 4], true)) {
                $status = 3;
            }

            $logId = intval($this->message['log_id'] ?? 0);
            if ($logId) {
                $log = $this->getRALogsFind(['id' => $logId], 'id,status');
            } else {
                $log = $this->getRALogsFind([
                    'machine_id' => $this->machine['machine_id'],
                    'type' => $msgType,
                    ['status', 'in', [1, 2]],
                ], 'id,status', 'id desc');
            }
            if (!$log) {
                actionLog($this->message, $msgType . ' 未匹配到远程动作日志');
                return 1;
            }
            $log = is_object($log) ? $log->toArray() : $log;

            $result = $this->updateRALog(
                ['status' => $status, 'operator_at' => date('Y-m-d H:i:s')],
                ['id' => $log['id']],
                ['status', 'operator_at']
            );
            actionLog($result, $msgType . ' 更新远程动作日志结果');
            return 0;
        } catch (\Exception $e) {
            actionException($e,1);
            return 1;
        }
    }

}
