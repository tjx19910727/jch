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
        if (!isset($this->data['mac']) && !isset($this->data['sign']))
            json(['state' => 100,"msg" => '缺少签名'])->send();
        if (isset($this->data['sign']) && $this->checkSign($this->data) !== true) {
            actionLog($this->data,'验签失败',"DataUpload");
            die(json_encode(["state" => 200, "msg" => "验签失败"],320));
        }
        $this->message = json2arr($this->data['data'] ?? "");
        actionLog($this->message, '消息数据', "DataUpload");
        if (!$this->message)
            json(['state' => 100,"msg" => 'message数据为空'])->send();
        try {
            validate(VReport::class)->scene('onMessage')->check($this->data);
        } catch (\Exception $e) {
            actionLog($e->getMessage(), '数据格式错误', 'DataUpload');
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
            if ($this->message) {
                $func_name = $this->message['msgType'] ?? "";
                if (method_exists(self::class, $func_name)) {
                    try {
                        validate(VReport::class)->scene($this->message['msgType'])->check($this->message);
                    } catch (\Exception $e) {
                        actionLog($e->getMessage(), '数据格式错误', 'DataUpload');
                        return 1;
                    }
                    return $this->$func_name();
                }
                actionLog($this->message,'没有对应的消息类型');
            }
            return 1;
        } catch (\Exception $e) {
            actionException($e,1);
            return 1;
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
     * 设备上传回收箱商品数量变化。
     * operate: 1 回收箱添加商品；2 回收箱取出商品；3 回收箱清空
     * type: 1 出货失败商品回收；2 远程回收商品
     * @return int
     */
    public function recycleBoxGoodsChange()
    {
        try {
            $operate = intval($this->message['operate'] ?? 0);
            if (!in_array($operate, [1, 2, 3], true)) {
                actionLog($this->message, 'recycleBoxGoodsChange 操作类型错误', 'DataUpload');
                return 1;
            }
            $recycleBoxChangeType = intval($this->message['type'] ?? 0);
            if (!in_array($recycleBoxChangeType, [1, 2], true)) {
                actionLog($this->message, 'recycleBoxGoodsChange 商品变化类型错误', 'DataUpload');
                return 1;
            }

            if ($operate === 3) {
                $goodsChanges = $this->getRecycleBoxCurrentGoodsChanges($recycleBoxChangeType);
            } else {
                $goodsChanges = $this->buildRecycleBoxGoodsChangesFromIds($this->message['g_ids'] ?? []);
                if (!$goodsChanges) {
                    actionLog($this->message, 'recycleBoxGoodsChange 商品ID为空', 'DataUpload');
                    return 1;
                }
            }

            $config = $this->getMachineConfigFind(['m_id' => $this->machine['m_id']], 'recycle_bin_capacity');
            $totalCapacity = intval($config['recycle_bin_capacity'] ?? 0);
            if ($totalCapacity < 0) {
                $totalCapacity = 0;
            }
            $currentRemain = intval($this->machine['recycle_box_remain_capacity'] ?? $totalCapacity);
            if ($currentRemain < 0 || $currentRemain > $totalCapacity) {
                $currentRemain = $totalCapacity;
            }

            $changeCount = $this->sumRecycleBoxGoodsChangeQuantity($goodsChanges);
            if ($operate === 1) {
                $remainCapacity = max(0, $currentRemain - $changeCount);
            } elseif ($operate === 2) {
                $remainCapacity = min($totalCapacity, $currentRemain + $changeCount);
            } else {
                $remainCapacity = $totalCapacity;
            }

            $this->startTrans();
            $flag = [];
            $flag[] = $this->updateMachine([
                'm_id' => $this->machine['m_id'],
                'recycle_box_total_capacity' => $totalCapacity,
                'recycle_box_remain_capacity' => $remainCapacity,
            ]);
            foreach ($goodsChanges as $goodsChange) {
                $flag[] = $this->addRecycleBoxGoodsChangeLog(
                    $goodsChange['g_id'],
                    $operate,
                    $recycleBoxChangeType,
                    $goodsChange['quantity']
                );
            }
            $result = $this->checkFlag($flag);
            $result ? $this->commitTrans() : $this->rollbackTrans();
            actionLog([
                'operate' => $operate,
                'type' => $recycleBoxChangeType,
                'goods_changes' => $goodsChanges,
                'total_capacity' => $totalCapacity,
                'remain_capacity' => $remainCapacity,
                'result' => $result,
            ], '回收箱商品数量变化处理结果', 'DataUpload');
            return $result ? 0 : 1;
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1, 'recycleBoxGoodsChange');
            return 1;
        }
    }

    protected function buildRecycleBoxGoodsChangesFromIds($gIds)
    {
        if (is_string($gIds)) {
            $gIds = explode(',', $gIds);
        } elseif (!is_array($gIds)) {
            $gIds = [$gIds];
        }

        $result = [];
        foreach ($gIds as $gId) {
            $gId = intval($gId);
            if ($gId > 0) {
                if (!isset($result[$gId])) {
                    $result[$gId] = [
                        'g_id' => $gId,
                        'quantity' => 0,
                    ];
                }
                $result[$gId]['quantity']++;
            }
        }
        return $result;
    }

    protected function getRecycleBoxCurrentGoodsChanges($recycleBoxChangeType)
    {
        $logs = $this->getGoodsChangeList([
            'm_id' => $this->machine['m_id'],
            'position' => 2,
            ['type', 'in', [2, 3]],
            'recycle_box_change_type' => $recycleBoxChangeType,
        ], 0, 'change_id,g_id,change_value,type', 'change_id asc');
        if (!$logs) {
            return [];
        }
        $logs = is_object($logs) ? $logs->toArray() : $logs;

        $goodsChanges = [];
        foreach ($logs as $log) {
            $gId = intval($log['g_id'] ?? 0);
            if ($gId <= 0) {
                continue;
            }
            $quantity = intval($log['change_value'] ?? 1);
            if ($quantity <= 0) {
                $quantity = 1;
            }
            if (!isset($goodsChanges[$gId])) {
                $goodsChanges[$gId] = [
                    'g_id' => $gId,
                    'quantity' => 0,
                ];
            }
            if (intval($log['type'] ?? 0) === 2) {
                $goodsChanges[$gId]['quantity'] += $quantity;
            } elseif (intval($log['type'] ?? 0) === 3) {
                $goodsChanges[$gId]['quantity'] = max(0, $goodsChanges[$gId]['quantity'] - $quantity);
            }
        }

        foreach ($goodsChanges as $gId => $goodsChange) {
            if (intval($goodsChange['quantity']) <= 0) {
                unset($goodsChanges[$gId]);
            }
        }
        return $goodsChanges;
    }

    protected function sumRecycleBoxGoodsChangeQuantity($goodsChanges)
    {
        $total = 0;
        foreach ($goodsChanges as $goodsChange) {
            $total += intval($goodsChange['quantity'] ?? 0);
        }
        return $total;
    }

    protected function addRecycleBoxGoodsChangeLog($gId, $operate, $recycleBoxChangeType, $changeValue = 1)
    {
        $goods = $this->getMachineGoodsFind(
            ['m_id' => $this->machine['m_id'], 'g_id' => $gId],
            'mg_id,g_id,g_name,gc_id,gc_name,pic,sku,bar_code'
        );
        if (!$goods) {
            $goods = $this->getGoodsFind(
                ['g_id' => $gId],
                'g_id,g_name,gc_id,gc_name,pic,sku,bar_code'
            );
        }
        $goods = $goods ? (is_object($goods) ? $goods->toArray() : $goods) : [];

        $descKey = $operate === 1 ? 'terminal_recycle_box_add_goods_' . $recycleBoxChangeType : 'terminal_recycle_box_remove_goods';
        if ($operate === 3) {
            $descKey = 'terminal_recycle_box_clear_goods';
        }

        $insert = [
            'm_id' => $this->machine['m_id'],
            'machine_id' => $this->machine['machine_id'],
            'machine_name' => $this->machine['machine_name'] ?? '',
            'mc_id' => 0,
            'channel_code' => '',
            'mg_id' => $goods['mg_id'] ?? 0,
            'g_id' => $gId,
            'g_name' => $goods['g_name'] ?? '',
            'gc_id' => $goods['gc_id'] ?? 0,
            'gc_name' => $goods['gc_name'] ?? '',
            'pic' => $goods['pic'] ?? '',
            'sku' => $goods['sku'] ?? '',
            'bar_code' => $goods['bar_code'] ?? '',
            'change_value' => intval($changeValue) > 0 ? intval($changeValue) : 1,
            'ao_id' => $this->machine['ao_id'] ?? 0,
            'desc' => $this->lang('goodsChange.' . $descKey),
            'position' => 2,
            'type' => $operate === 1 ? 2 : 3,
            'recycle_box_change_type' => $recycleBoxChangeType,
        ];
        $changeId = $this->addGoodsChange($insert);
        actionLog(['change_id' => $changeId, 'data' => $insert], '【SQL】添加回收箱商品变化日志', 'DataUpload');
        return $changeId;
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
