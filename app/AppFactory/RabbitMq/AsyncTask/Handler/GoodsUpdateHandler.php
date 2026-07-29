<?php

namespace app\AppFactory\RabbitMq\AsyncTask\Handler;

use app\AppFactory\AppFactory;
use app\AppFactory\RabbitMq\AsyncTask\AsyncTaskHandlerInterface;
use think\facade\Db;

/**
 * 商品更新后通知设备同步任务。
 */
class GoodsUpdateHandler implements AsyncTaskHandlerInterface
{
    /**
     * @param array $payload
     * @param array $task
     */
    public function handle($payload, $task = [])
    {
        $gId = $payload['g_id'] ?? 0;
        if (!$gId) {
            throw new \InvalidArgumentException('商品更新异步任务缺少g_id');
        }

        $mgList = Db::name('machine_goods')
            ->where('g_id', $gId)
            ->field('mg_id,machine_id')
            ->select()
            ->toArray();
        foreach ($mgList as $mg) {
            $this->sendToMachine($mg['machine_id'], 'updateMg', ['mg_id' => $mg['mg_id']]);
        }

        $mcList = Db::name('machine_channel')
            ->where('g_id', $gId)
            ->field('mc_id,machine_id')
            ->select()
            ->toArray();
        foreach ($mcList as $mc) {
            $this->sendToMachine($mc['machine_id'], 'updateMc', ['mc_id' => $mc['mc_id']]);
        }

        $result = [
            'g_id' => $gId,
            'machine_goods_count' => count($mgList),
            'machine_channel_count' => count($mcList),
        ];
        actionLog([
            'task_id' => $task['task_id'] ?? '',
            'result' => $result,
        ], '商品更新-下发设备同步完成', 'async_task_goods_update');

        return $result;
    }

    /**
     * @param string $machineId
     * @param string $msgType
     * @param array $data
     */
    protected function sendToMachine($machineId, $msgType, $data = [])
    {
        $machine = Db::name('machine')
            ->where('machine_id', $machineId)
            ->field('machine_id,mac_address,signKey,online')
            ->find();
        if (!$machine || $machine['online'] != 1) {
            return false;
        }

        $key = $machine['signKey'] ?: env('api.md5Key');
        if (!$key) {
            return false;
        }

        $app = AppFactory::machine([
            'machine_id' => $machine['machine_id'],
            'key' => $key,
            'mac' => $machine['mac_address'] ?? '',
        ]);
        return $app->sendMq->sendMq($msgType, $data);
    }
}
