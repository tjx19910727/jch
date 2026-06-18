<?php
/**
 * Created by Codex.
 * User: Administrator
 * Date: 2026/6/16
 * Time: 0:00
 */

namespace app\AppFactory\Kernel\Traits\Api;

use app\AppFactory\Kernel\Support\ApiOutStatusNotify;

trait ApiOutStatusNotifyTrait
{
    use ApiCallbackTrait;

    /**
     * 对外推送订单出货状态。
     * 日志表：api_callback，兼作发送队列、发送结果、重试记录。
     * 接收地址由 ApiOutStatusNotify::API_OUT_BASE_URL 拼接，不放入 config。
     * event: ready-准备出货 success-出货成功 fail-出货失败
     */
    protected function addOrderOutStatusCallback($event, array $order = [])
    {
        try {
            return $this->doAddOrderOutStatusCallback($event, $order);
        } catch (\Throwable $e) {
            try {
                actionLog([
                    'event' => $event,
                    'trade_no' => $order['trade_no'] ?? ($this->order['trade_no'] ?? ''),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], '订单出货状态通知异常，已忽略不影响出货流程', 'OutStatusNotify');
            } catch (\Throwable $logException) {
            }
            return false;
        }
    }

    protected function doAddOrderOutStatusCallback($event, array $order = [])
    {
        $order = $order ?: ($this->order ?? []);
        if (is_object($order)) {
            $order = method_exists($order, 'toArray') ? $order->toArray() : (array)$order;
        }
        if (!$order || empty($order['trade_no'])) {
            actionLog($order, '订单出货状态通知缺少订单数据', 'OutStatusNotify');
            return false;
        }
        if (!$this->isMobileVendingMachineOrder($order)) {
            actionLog([
                'trade_no' => $order['trade_no'] ?? '',
                'machine_id' => $order['machine_id'] ?? '',
                'machine_level' => $order['machine_level'] ?? ($this->machine['machine_level'] ?? null),
            ], '非移动售卖机订单，跳过订单出货状态通知', 'OutStatusNotify');
            return false;
        }

        $callback = $this->resolveOrderOutStatusCallbackUrl($order);

        $outStatus = intval($order['out_status'] ?? 0);
        $message = [
            'event' => $event,
            'event_desc' => $this->getOrderOutStatusEventDesc($event),
            'order_id' => intval($order['order_id'] ?? 0),
            'trade_no' => $order['trade_no'],
            'machine_id' => $order['machine_id'] ?? '',
            'machine_name' => $order['machine_name'] ?? '',
            'out_status' => $outStatus,
            'out_status_desc' => $this->getOrderOutStatusDesc($outStatus),
            'notify_time' => date('Y-m-d H:i:s'),
        ];

        $insertCallback = [
            'aa_id' => $callback['aa_id'],
            'notify_url' => $callback['notify_url'],
            'callback_type' => 10,
            'message' => json_encode($message, 320),
        ];
        $acId = $this->addApiCallback($insertCallback);
        actionLog([
            'ac_id' => $acId,
            'log_table' => 'api_callback',
            'callback' => $insertCallback,
        ], '添加订单出货状态通知记录', 'OutStatusNotify');
        return $acId;
    }

    protected function resolveOrderOutStatusCallbackUrl(array $order): array
    {
        $result = [
            'aa_id' => 0,
            'notify_url' => ApiOutStatusNotify::getOrderOutStatusNotifyUrl(),
        ];

        if (method_exists($this, 'getApiAdvanceFind')) {
            $advance = $this->getApiAdvanceFind(['trade_no' => $order['trade_no']], 'aa_id');
            if ($advance) {
                $advance = is_object($advance) ? (method_exists($advance, 'toArray') ? $advance->toArray() : (array)$advance) : $advance;
                $result['aa_id'] = intval($advance['aa_id'] ?? 0);
            }
        }

        return $result;
    }

    protected function isMobileVendingMachineOrder(array $order): bool
    {
        $machineLevel = $order['machine_level'] ?? ($this->machine['machine_level'] ?? null);
        if ($machineLevel !== null && $machineLevel !== '') {
            return in_array(intval($machineLevel), ApiOutStatusNotify::MOBILE_VENDING_MACHINE_LEVELS, true);
        }

        if (!method_exists($this, 'getMachineFind')) {
            return false;
        }

        $where = [];
        if (!empty($order['machine_id'])) {
            $where['machine_id'] = $order['machine_id'];
        } elseif (!empty($order['m_id'])) {
            $where['m_id'] = $order['m_id'];
        }
        if (!$where) {
            return false;
        }

        $machine = $this->getMachineFind($where, 'machine_level');
        if (!$machine) {
            return false;
        }
        $machine = is_object($machine) ? (method_exists($machine, 'toArray') ? $machine->toArray() : (array)$machine) : $machine;
        return in_array(intval($machine['machine_level'] ?? 0), ApiOutStatusNotify::MOBILE_VENDING_MACHINE_LEVELS, true);
    }

    protected function getOrderOutStatusDesc($outStatus): string
    {
        $map = [
            1 => '待取货',
            2 => '已发出货命令',
            3 => '设备已接收',
            4 => '出货成功',
            5 => '出货失败',
            6 => '未提货',
        ];
        return $map[intval($outStatus)] ?? '未知状态';
    }

    protected function getOrderOutStatusEventDesc($event): string
    {
        $map = [
            'ready' => '准备出货',
            'success' => '出货成功',
            'fail' => '出货失败',
        ];
        return $map[$event] ?? '出货状态变更';
    }

    /**
     * 对外同步设备开关机时间。
     * 日志表：api_callback，兼作发送队列、发送结果、重试记录。
     * 接收地址由 ApiOutStatusNotify::GET_MACHINE_ONOFF_CKC_TIME 拼接，不放入 config。
     */
    protected function addMachineOnOffCkcTimeCallback(array $machineOnOff, $event = 'update', array $changedFields = [])
    {
        try {
            return $this->doAddMachineOnOffCkcTimeCallback($machineOnOff, $event, $changedFields);
        } catch (\Throwable $e) {
            try {
                actionLog([
                    'event' => $event,
                    'moo_id' => $machineOnOff['moo_id'] ?? 0,
                    'm_id' => $machineOnOff['m_id'] ?? 0,
                    'machine_id' => $machineOnOff['machine_id'] ?? '',
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
            ], '设备开关机时间同步异常，已忽略不影响后台流程', 'OutStatusNotify');
            } catch (\Throwable $logException) {
            }
            return false;
        }
    }

    protected function doAddMachineOnOffCkcTimeCallback(array $machineOnOff, $event = 'update', array $changedFields = [])
    {
        if (!$machineOnOff || (empty($machineOnOff['machine_id']) && empty($machineOnOff['m_id']))) {
            actionLog($machineOnOff, '设备开关机时间同步缺少设备数据', 'OutStatusNotify');
            return false;
        }
        if (!$this->isMobileVendingMachineOrder($machineOnOff)) {
            actionLog([
                'moo_id' => $machineOnOff['moo_id'] ?? 0,
                'm_id' => $machineOnOff['m_id'] ?? 0,
                'machine_id' => $machineOnOff['machine_id'] ?? '',
                'machine_level' => $machineOnOff['machine_level'] ?? ($this->machine['machine_level'] ?? null),
            ], '非移动售卖机设备，跳过设备开关机时间同步', 'OutStatusNotify');
            return false;
        }

        $message = [
            'event' => $event,
            'event_desc' => $this->getMachineOnOffCkcTimeEventDesc($event),
            'changed_fields' => array_values(array_unique($changedFields)),
            'moo_id' => intval($machineOnOff['moo_id'] ?? 0),
            'm_id' => intval($machineOnOff['m_id'] ?? 0),
            'machine_id' => $machineOnOff['machine_id'] ?? '',
            'machine_name' => $machineOnOff['machine_name'] ?? '',
            'machine_level' => intval($machineOnOff['machine_level'] ?? 0),
            'ao_id' => intval($machineOnOff['ao_id'] ?? 0),
            'status' => intval($machineOnOff['status'] ?? 0),
            'on_off_machine' => $machineOnOff['on_off_machine'] ?? '',
            'on_off_machine_list' => $this->normalizeMachineOnOffTimeList($machineOnOff['on_off_machine'] ?? ''),
            'notify_time' => date('Y-m-d H:i:s'),
        ];

        $insertCallback = [
            'aa_id' => 0,
            'notify_url' => ApiOutStatusNotify::getMachineOnOffCkcTimeUrl(),
            'callback_type' => 11,
            'message' => json_encode($message, 320),
        ];
        $acId = $this->addApiCallback($insertCallback);
        actionLog([
            'ac_id' => $acId,
            'log_table' => 'api_callback',
            'callback' => $insertCallback,
        ], '添加设备开关机时间同步记录', 'OutStatusNotify');
        return $acId;
    }

    protected function normalizeMachineOnOffTimeList($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        $value = trim((string)$value);
        if ($value === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function getMachineOnOffCkcTimeEventDesc($event): string
    {
        $map = [
            'add' => '新增设备开关机营业时间',
            'update' => '修改设备开关机时间',
            'import_on_off' => '导入设备开关机时间',
            'test' => '测试设备开关机时间同步',
        ];
        return $map[$event] ?? '设备开关机时间变更';
    }
}
