<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:50
 */

namespace app\management\controller\machine;


use app\AppFactory\AppFactory;
use app\management\controller\Common;
use app\management\validate\Machine\VMachine;
use app\AppFactory\Kernel\Traits\Machine\MachineErrorCodeTrait;
use app\AppFactory\Kernel\Traits\SaleOrders\SaleOrdersTrait;
use app\AppFactory\Kernel\Model\Machine\MachineModel;

use app\AppFactory\Kernel\Traits\Payment\AfterOrderPaymentTrait;
class Machine extends Common
{
    use MachineErrorCodeTrait,SaleOrdersTrait,AfterOrderPaymentTrait;

    protected $field = "*";
    protected $validatePath = VMachine::class;

    public function getList()
    {
        $postData = input();
        $machineIds = [];
        if (isset($postData['machine_group_id']) && $postData['machine_group_id']) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']],'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }
        if(isset($postData['online_all'])){
             $postData['online'] = $postData['online_all'];
            unset($postData['online_all']);
        }
        $pageNum = $postData['pageNum'] ?? 0;
        $field = $this->field;
        $order = $this->buildMachineListOrder($postData, $field);
        $isOnOff = $postData['is_on_off'] ?? 0;
        unset($postData['version_sort'],$postData['stock_ratio'],$postData['sort_name'],$postData['sort_order'],$postData['is_on_off']);
        $filterCurrency = '';
        if (isset($postData['currency_code']) && trim((string)$postData['currency_code']) !== '') {
            $filterCurrency = strtoupper(trim((string)$postData['currency_code']));
            unset($postData['currency_code']);
        }
        // 提取 online 参数，单独处理：1=在线(http_online或online为1)，2=离线(http_online和online都为2)
        $onlineValue = null;
        if (isset($postData['online']) && $postData['online'] !== '') {
            $onlineValue = $postData['online'];
            unset($postData['online']);
        }
        $where = $this->getWhere($postData, false, ["machine_name" => "like"]);
        //只取vending_machine_type为1的设备，即主柜设备
        $where[] = ['vending_machine_type', '=', 1];//vending_machine_type字段已废弃，入库默认值为1，代码层面涉及此字段的不用管
        if (!empty($machineIds)) $where[] = ['machine_id', 'in',$machineIds];
        // 按设备当前币种（人民币/港币）筛选：currency_code 在 machine_config 表
        if ($filterCurrency && !preg_match('/^[A-Z]{3}$/', $filterCurrency)) $filterCurrency = '';
        if ($filterCurrency) {
            $where['raw'] = (isset($where['raw']) ? $where['raw'] . ' AND ' : '')
                . "EXISTS(SELECT 1 FROM machine_config cfg WHERE cfg.m_id = a.m_id AND cfg.currency_code = '" . $filterCurrency . "')";
        }

        // 处理 is_on_off 筛选：1=当前时间在营业时间内，2=当前时间不在营业时间内
        if ($isOnOff) {
            $where[] = $this->buildIsOnOffWhere($isOnOff);
        }

        // 处理 online 筛选条件
        if ($onlineValue !== null) {
            if ($onlineValue == 1) {
                // online=1: http_online=1 或 online=1
                $where['raw'] = (isset($where['raw']) ? $where['raw'] . ' AND ' : '') . '(a.http_online = 1 OR a.online = 1)';
            } else {
                // online=2: http_online=2 且 online=2
                $where[] = ['http_online', '=', 2];
                $where[] = ['online', '=', 2];
            }
        }
        return $this->app->machine->getMList($where,$pageNum,$field,$order);
    }

    private function buildMachineListOrder($postData, &$field)
    {
        $orderList = [];

        $sortName = strtolower(trim((string)($postData['sort_name'] ?? '')));
        $sortOrder = $this->normalizeSortDirection($postData['sort_order'] ?? '');

        if ($sortName) {
            if (!$sortOrder) {
                $sortOrder = 'desc';
            }
            if ($sortName == 'id') {
                $sortName = 'm_id';
            }
            if ($sortName == 'machine_name') {
                $sortName = 'machine_id';
            }

            $normalSortFieldMap = [
                'm_id' => 'm_id',
                'machine_id' => 'machine_id',
                'machine_name' => 'machine_name',
                'online' => 'online',
                'last_online_time' => 'last_online_time',
                'version' => 'version',
                'is_operating' => 'is_operating',
                'status' => 'status',
                'factory' => 'factory',
                'inventory_location' => 'inventory_location',
            ];

            $specialSortAlias = $this->appendSpecialSortField($sortName, $field);
            if ($specialSortAlias) {
                $orderList[] = "{$specialSortAlias} {$sortOrder}";
                if ($sortName == 'month_achieve_rate') {
                    $this->appendSpecialSortField('month_achieve_amount', $field);
                    $orderList[] = "month_achieve_amount_sort {$sortOrder}";
                }
            } elseif (isset($normalSortFieldMap[$sortName])) {
                $orderList[] = "{$normalSortFieldMap[$sortName]} {$sortOrder}";
            }
        }

        // 兼容旧参数逻辑：即使 sort_name 有值，也继续追加到后面
        if (!empty($postData['version_sort'])) {
            $versionDirection = $postData['version_sort'] == 1 ? 'asc' : 'desc';
            $orderList[] = "version {$versionDirection}";
        }
        if (!empty($postData['stock_ratio'])) {
            $stockRatioDirection = $postData['stock_ratio'] == 1 ? 'asc' : 'desc';
            $this->appendSelectField($field, 'stock_ratio_sort', "(SELECT IF(SUM(capacity) > 0, LEAST(GREATEST(SUM(stock) / SUM(capacity), 0), 1), 0) FROM machine_channel WHERE m_id = a.m_id AND status <> 2)");
            $orderList[] = "stock_ratio_sort {$stockRatioDirection}";
        }

        $orderList[] = 'online asc';
        $orderList[] = 'm_id desc';
        return implode(', ', $orderList);
    }

    private function appendSpecialSortField($sortName, &$field)
    {
        if ($sortName == 'stock_ratio') {
            $this->appendSelectField($field, 'stock_ratio_sort', "(SELECT IF(SUM(capacity) > 0, LEAST(GREATEST(SUM(stock) / SUM(capacity), 0), 1), 0) FROM machine_channel WHERE m_id = a.m_id AND status <> 2)");
            return 'stock_ratio_sort';
        }

        if ($sortName == 'month_target_amount') {
            $month = date('Y-m');
            $this->appendSelectField(
                $field,
                'month_target_amount_sort',
                $this->buildMonthTargetAmountExpression($month)
            );
            return 'month_target_amount_sort';
        }

        if ($sortName == 'month_achieve_amount') {
            $monthStart = strtotime(date('Y-m-01 00:00:00'));
            $monthEnd = strtotime(date('Y-m-t 23:59:59'));
            $this->appendSelectField(
                $field,
                'month_achieve_amount_sort',
                "(SELECT IFNULL(SUM(total_price - refund_amount),0) FROM sale_orders WHERE m_id = a.m_id AND pay_status = 3 AND create_date >= {$monthStart} AND create_date <= {$monthEnd})"
            );
            return 'month_achieve_amount_sort';
        }

        if ($sortName == 'month_achieve_rate') {
            $month = date('Y-m');
            $monthStart = strtotime(date('Y-m-01 00:00:00'));
            $monthEnd = strtotime(date('Y-m-t 23:59:59'));
            $targetAmountExpression = $this->buildMonthTargetAmountExpression($month);
            $this->appendSelectField(
                $field,
                'month_achieve_rate_sort',
                "(IF({$targetAmountExpression} > 0, ((SELECT IFNULL(SUM(total_price - refund_amount),0) FROM sale_orders WHERE m_id = a.m_id AND pay_status = 3 AND create_date >= {$monthStart} AND create_date <= {$monthEnd}) / {$targetAmountExpression} * 100), 0))"
            );
            return 'month_achieve_rate_sort';
        }

        if ($sortName == 'rsrp') {
            $todayStart = date('Y-m-d 00:00:00');
            $todayEnd = date('Y-m-d 23:59:59');
            $this->appendSelectField($field, 'rsrp_sort', "(SELECT IFNULL(rsrp, -999) FROM sim_signal_log WHERE m_id = a.m_id AND created_at >= '{$todayStart}' AND created_at <= '{$todayEnd}' ORDER BY id DESC LIMIT 1)");
            return 'rsrp_sort';
        }

        if ($sortName == 'machine_on_off') {
            $this->appendSelectField($field, 'machine_on_off_sort', "(SELECT IFNULL(on_off_machine, '') FROM machine_on_off WHERE m_id = a.m_id AND status = 1 LIMIT 1)");
            return 'machine_on_off_sort';
        }

        return '';
    }

    private function buildMonthTargetAmountExpression($month)
    {
        $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $month)
            ? (string) $month
            : date('Y-m');

        return "COALESCE("
            . "(SELECT SUM(mt.target_amount) FROM machine_target_monthly mt WHERE mt.m_id = a.m_id AND mt.month = '{$month}'),"
            . "(SELECT mtg.target_amount FROM machine_target_group mtg "
            . "WHERE mtg.m_id = a.m_id AND mtg.months <= '{$month}' "
            . "ORDER BY mtg.months DESC LIMIT 1),0)";
    }

    private function appendSelectField(&$field, $alias, $expression)
    {
        if (strpos($field, " {$alias}") !== false) {
            return;
        }
        $field .= ", {$expression} {$alias}";
    }

    private function normalizeSortDirection($direction)
    {
        $direction = strtolower(trim((string)$direction));
        if ($direction == 'asc' || $direction == 'desc') {
            return $direction;
        }
        return '';
    }

    /**
     * 根据 is_on_off 参数生成设备 m_id 筛选条件
     * @param int $isOnOff 1=在营业时间内，2=不在营业时间内
     * @return array
     */
    private function buildIsOnOffWhere($isOnOff)
    {
        $weekDay = date('N') - 1; // 0=周一, 6=周日
        $currentTime = date('H:i');

        $onOffList = $this->app->machine->getMachineOnOffList(['status' => 1], 0, 'm_id,on_off_machine');
        $allScheduleIds = [];
        $matchIds = [];

        if ($onOffList) {
            foreach ($onOffList as $item) {
                $allScheduleIds[] = $item['m_id'];

                $onOffMachine = $item['on_off_machine'];
                if (is_string($onOffMachine)) {
                    $onOffMachine = json_decode($onOffMachine, true);
                }
                if (!is_array($onOffMachine)) continue;

                $dayKey = (string)$weekDay;
                if (!isset($onOffMachine[$dayKey]) || empty($onOffMachine[$dayKey])) continue;
                if ($onOffMachine[$dayKey] === 'null' || $onOffMachine[$dayKey] === '{}') continue;

                $timeRange = explode(',', $onOffMachine[$dayKey]);
                if (count($timeRange) !== 2) continue;

                // 数据库存的顺序是反的（关机时间,开机时间），这里反转后按正常顺序处理
                $timeRange = array_reverse($timeRange);
                $startupTime = trim($timeRange[0]); // 开机时间
                $shutdownTime = trim($timeRange[1]); // 关机时间

                if (!$startupTime || !$shutdownTime || $startupTime === 'null' || $shutdownTime === 'null') continue;

                // 判断当前时间是否在营业范围内
                if ($shutdownTime > $startupTime) {
                    // 同天（如反转后07:00,22:00）：营业时间为开机时间~关机时间（07:00~22:00）
                    $isInRange = ($currentTime >= $startupTime && $currentTime <= $shutdownTime);
                } else {
                    // 跨天（如反转后07:00,02:00）：营业时间为开机时间~关机时间，跨次日凌晨（07:00~02:00）
                    $isInRange = ($currentTime >= $startupTime || $currentTime <= $shutdownTime);
                }

                if ($isInRange) {
                    $matchIds[] = $item['m_id'];
                }
            }
        }

        $matchIds = array_unique($matchIds);
        $allScheduleIds = array_unique($allScheduleIds);

        if ($isOnOff == 1) {
            // 查询当前时间在营业时间范围内的设备
            if (empty($matchIds)) {
                return ['m_id', '=', 0];
            }
            return ['m_id', 'in', $matchIds];
        }

        // isOnOff == 2：查询当前时间不在营业时间范围内的设备（取有配置但不在范围内的差集）
        $notInRangeIds = array_diff($allScheduleIds, $matchIds);
        if (empty($notInRangeIds)) {
            return ['m_id', '=', 0];
        }
        return ['m_id', 'in', $notInRangeIds];
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machine->getMFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        // 要求国家/省/市编码必传，regions_id 可选
        foreach (['country_id', 'state_id', 'city_id'] as $f) {
            if (empty($postData[$f]) && $postData[$f] !== 0) {
                // 使用通用提示，若需要可在语言文件中添加专用提示键
                return returnValidate(lang('VMachine.' . $f . '_require'));
            }
        }
        return $this->app->machine->addM($postData);
    }

    public function update()
    {
        $postData = input();
        if (isset($postData['machine_manager_id'])) {
            $postData['manager_id'] = $postData['machine_manager_id'];
            unset($postData['machine_manager_id']);
        }
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        // 要求国家/省/市编码必传，regions_id 可选
        foreach (['country_id', 'state_id', 'city_id'] as $f) {
            if (empty($postData[$f]) && $postData[$f] !== 0) {
                // 使用通用提示，若需要可在语言文件中添加专用提示键
                return returnValidate(lang('VMachine.' . $f . '_require'));
            }
        }
        return $this->app->machine->updateM($postData);
    }

    public function updateMore()
    {
        $postData = input();
        return $this->app->machine->updateMore($postData);
    }

    /**
     * 设置单个设备在营状态
     * @return array|string
     */
    public function setOperating()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.setOperating');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->setOperating($postData);
    }

    /**
     * 批量设置设备在营状态
     * @return array|string
     */
    public function setOperatingBatch()
    {
        $postData = input();
        return $this->app->machine->setOperatingBatch($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machine->delM($postData['m_id']);
    }

    /**
     * 导出设备
     * @return array|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exportMachine()
    {
        $postData = input();
        if (isset($postData['lang'])) unset($postData['lang']);
        $machineIds = [];
        if (isset($postData['machine_group_id']) && $postData['machine_group_id']) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']],'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }
        if(isset($postData['online_all'])){
            $postData['online'] = $postData['online_all'];
            unset($postData['online_all']);
        }
        $isOnOff = $postData['is_on_off'] ?? 0;
        unset($postData['pageNum'], $postData['version_sort'], $postData['stock_ratio'], $postData['sort_name'], $postData['sort_order'], $postData['is_on_off']);

        $onlineValue = null;
        if (isset($postData['online']) && $postData['online'] !== '') {
            $onlineValue = $postData['online'];
            unset($postData['online']);
        }
        
        $where = $this->getWhere($postData, false, ["machine_name" => "like"]);
        
        if (!empty($machineIds)) $where[] = ['machine_id', 'in',$machineIds];
        $field = "m_id,machine_id,machine_name,ao_id,country_id,state_id,city_id,regions_id,street,floor,version,factory,inventory_location,
        IFNULL((SELECT GROUP_CONCAT(DISTINCT mg.mg_name ORDER BY mg.id SEPARATOR ',') FROM machine_group_mg mg WHERE mg.m_id = a.m_id),'') machine_group_name,
        (case online when 1 then '" . lang("online") . "' else '" . lang("offline"). "' END) online,
        FROM_UNIXTIME(last_online_time) last_online_time,
        (case device_type when 1 then '" . lang("vending_machine") . "' else '" . lang("store") . "' end) device_type,
        (case machine_level when 1 then '" . lang("simplified_version") . "' else '" . lang("luxury_edition") . "' END) machine_level,
        (SELECT CASE mc.run_mode WHEN 2 THEN '测试模式' ELSE '生产模式' END FROM machine_config mc WHERE mc.m_id = a.m_id LIMIT 1) run_mode,
    (case is_operating when 1 then '在营' when 2 then '在库' when 3 then '外售' END) is_operating,
        (case status when 1 then '" . lang("normal") . "' when 2 then '" . lang("disable") . "' when 3 then '" . lang("maintenance") . "' end) status";
        //只取vending_machine_type为1的设备，即主柜设备
        $where[] = ['vending_machine_type', '=', 1];
        if ($isOnOff) {
            $where[] = $this->buildIsOnOffWhere($isOnOff);
        }
        if ($onlineValue !== null) {
            if ($onlineValue == 1) {
                $where['raw'] = (isset($where['raw']) ? $where['raw'] . ' AND ' : '') . '(a.http_online = 1 OR a.online = 1)';
            } else {
                $where[] = ['http_online', '=', 2];
                $where[] = ['online', '=', 2];
            }
        }
        return $this->app->machine->exportM($where,$field,"machine_id desc");
    }

    /**
     * 发送主体控制命令
     * @return array|string
     */
    public function sendMainControl()
    {
        try {
            $postData = input();
            $otherData = ["time_point" => (isset($postData['time_point']) && $postData['time_point'] ? strtotime($postData['time_point']) : time())];
            $typeList = [
                1 => "sleep",
                2 => "wakeUp",
                3 => "reboot",
                4 => "shutdown",
                5 => "update",
                6 => "powerWakeUp",
                7 => "initialization",
                8 => "backHome",
                9 => "shield",
                10 => "shield",
                11 => "autoRestocking",
            ];
            if (empty($postData['machine_id'])) return returnValidate(lang("VMachine.machine_id_require"));
            if (isset($postData['msgType']) && (is_int($postData['msgType']) || ctype_digit((string)$postData['msgType']))) {
                $msgType = intval($postData['msgType']);
                if (!isset($typeList[$msgType])) return returnValidate(lang("VMachine.msg_type_invalid"));
                $postData['msgType'] = $typeList[$msgType];
                if ($msgType === 4 && isset($postData['on_time'])) {
                    if (!is_string($postData['on_time']) && !is_numeric($postData['on_time'])) {
                        return returnValidate(lang("VMachine.on_time_format_invalid"));
                    }
                    $onTime = trim((string)$postData['on_time']);
                    if ($onTime !== '') {
                        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/D', $onTime)) {
                            return returnValidate(lang("VMachine.on_time_format_invalid"));
                        }
                        if (strcmp($onTime, date('H:i')) <= 0) {
                            return returnValidate(lang("VMachine.on_time_must_be_later_today"));
                        }
                        $otherData['on_time'] = $onTime;
                    }
                }
                if ($msgType === 9 || $msgType === 10) {
                    $otherData['status'] = $msgType === 9 ? 1 : 2;
                }

                if ($msgType == 11) {
                    $channelCode = trim((string)($postData['channel_code'] ?? ''));
                    if ($channelCode === '') return returnValidate(lang('VMachineChannel.channel_code_require'));
                    $channel = $this->app->machineChannel->getMachineChannelFind([
                        'machine_id' => $postData['machine_id'],
                        'channel_code' => $channelCode,
                    ], 'mc_id');
                    if (!$channel) return returnValidate(lang('VMachineChannel.mc_data_empty'));
                    $otherData['channel_code'] = $channelCode;
                }
            }

            $machineLevelLimit = [
                "shield" => 1,
            ];
            $resolvedMsgType = $postData['msgType'] ?? '';
            if (isset($machineLevelLimit[$resolvedMsgType])) {
                $machineLevel = MachineModel::where('machine_id', $postData['machine_id'])->value('machine_level');
                if ($machineLevel === null) return returnValidate(lang("VMachine.machine_no_data"));
                if (intval($machineLevel) !== $machineLevelLimit[$resolvedMsgType]) {
                    $langKey = $machineLevelLimit[$resolvedMsgType] === 1
                        ? "VMachine.simplified_command_only"
                        : "VMachine.luxury_command_only";
                    return returnValidate(lang($langKey));
                }
            }
            if(!empty($postData['powerTime'])) {
                $Mchtime = explode(',',$postData['powerTime']);
                $Mchtime[0] = !empty($Mchtime[0])?strtotime($Mchtime[0]):0;
                $Mchtime[1] = !empty($Mchtime[1])?strtotime($Mchtime[1]):0;
                $otherData['powerTime'] = $Mchtime[0]+$Mchtime[1];
            }
            $result = $this->app->machine->sendToMachine($postData, $postData['msgType'], $otherData);
            return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->app->machine->rTryCatch($e->getMessage());
        }
    }

    /**
     * 设置灯光亮度
     * @return array|string
     */
    public function setLight()
    {
        $machine_id = input("machine_id");
        $light = input("light");
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        if (!$light) return returnValidate(lang("VMachine.light_require"));
        if ($light%10 != 0) return returnValidate(lang("VMachine.light_multiple"));
        $otherData  = ["value" => $light];
        $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id],"light",$otherData);
        return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
    }

    /**
     * 设置设备暂停营业 setMachineCkcOnOff
     * @return array|string
     */
    public function setMachineCkcOnOff()
    {
        //这里严谨一点需要验证是否在开机时间范围内、营业时间范围内、逻辑复杂，暂时不这么搞，后续有需求再改
        $machine_id = input("machine_id");
        $ckc_status = input("ckc_status");
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        if (!$ckc_status) return returnValidate(lang("VMachine.ckc_status_require"));
        $otherData  = ["ckc_status" => $ckc_status];
        $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], "machineCkcOnOff", $otherData);
        //延时2s返回，等待mq上报状态
        sleep(2);
        return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
    }

    /**
     * 设置音量
     * @return array|string
     */
    public function setVolume()
    {
        $machine_id = input("machine_id");
        $volume = input("volume");
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        if (!$volume) return returnValidate(lang("VMachine.volume_require"));
        if ($volume%10 != 0) return returnValidate(lang("VMachine.volume_multiple"));
        $otherData  = ["value" => $volume];
        $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id],"volume",$otherData);
        return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
    }

    /**
     * 批量发送主体控制指令
     * @return array|string
     */
    public function sendAllControl()
    {
        try {
            $postData = input();
            $otherData = ["time_point" => (isset($postData['time_point']) && $postData['time_point'] ? strtotime($postData['time_point']) : time())];
            $lightArr = ["time_point" => (isset($postData['time_point']) && $postData['time_point'] ? strtotime($postData['time_point']) : time())];
            if (isset($postData['msgType']) && is_int($postData['msgType'])) {
                $typeList = [1 => "sleep", 2 => "wakeUp",3 => "machineCkcOnOff",5 =>"shutdown",6 =>"reboot"];
                $postData['msgType'] = $typeList[$postData['msgType']];
            }
            $postData['machine_id'] = explode(',',$postData['machine_id']);
            if ($postData['msgType'] === 'shutdown' && count($postData['machine_id']) > 10) {
                return returnValidate('批量关机一次最多只能选择10台机器');
            }
            if ($postData['msgType'] === 'reboot' && count($postData['machine_id']) > 20) {
                return returnValidate('批量重启一次最多只能选择20台机器');
            }
            $result = $this->app->machine->sendToArrMachine($postData, $postData['msgType'], $otherData);
            if(!$result) $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
            if($postData['msgType'] == 'sleep') $lightArr = ['value' => 0];
            else $lightArr = ['value' => 100];
            $result = $this->app->machine->sendToArrMachine($postData,"light",$lightArr);
            return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->app->machine->rTryCatch($e->getMessage());
        }
    }

    /**
     * 获取设备开锁密码
     * @return string
     */
    public function getOpenPass(){
        $getData = input();
        try{
            return returnData($this->app->machine->getPass($getData['machine_id']));
        } catch (\Exception $e){
            return $this->app->machine->rFail('获取失败');
        }
    }

    /**
     * 远程动作 doorOpen powerWakeUp initialization axisOffset
     * @return array|string
     */
    public function remoteAction()
    {
        $machine_id = input("machine_id");
        $type = input('type');
        $otherData = input("otherData") ?? [];
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        if ($type == 'axisOffset'){
            if(!$otherData['x_axis'] && !$otherData['y_axis']) return returnValidate(lang("VMachine.x_y_axis_require"));
        }
        if($type == 'doorOpen'){
            $otherData['creator_id'] = $this->manager['manager_id'] ?? 0;
        }
        $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], $type, $otherData);
        return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
    }

    /**
     * 远程出货
     * @return array|string
     */
    public function remoteOutGoods(){
        $postData = input();
        $result = $this->app->machine->setRemoteOutGoods($postData);
        return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
    }


    // 远程退货动作组 获取回收箱当前数量、打开出料箱门、关闭出料箱门、拍照上传、回收商品 checkRecycleBox、pickUpDoorOpen、pickUpDoorClose、takePhotos、recycGoods
    /**
     * 远程退货动作组 获取回收箱当前数量、剩余数量
     * @return array|string
     */
    public function getRecycleBoxInfo(){
        $machine_id = input("machine_id");
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        $send = 0;
        $n = 0;
        while (1) {
            $machine = $this->app->machine->getMachineFind(
                ['machine_id' => $machine_id],
                'machine_id,recycle_box_total_capacity,recycle_box_remain_capacity'
            );
            if (!$machine) return $this->app->machine->rFail($this->app->machine->lang("VMachine.machine_no_data"));
            if ($machine['recycle_box_remain_capacity'] != '-1') {
                return returnState(200, lang("query_success"), $machine);
            }
            if (!$send) {
                $this->app->machine->sendToMachine(['machine_id' => $machine_id], "checkRecycleBox", []);
                $send = 1;
            }
            sleep(1);
            $n++;
            if ($n >= 20) {
                return returnState(300, lang("VMachine.get_recycle_box_overtime"));
            }
        }
    }

    public function setPickUpDoorOpen(){
        $machine_id = input("machine_id");
        return $this->waitRemoteActionLogResult($machine_id, "pickUpDoorOpen");
    }

    public function setPickUpDoorClose(){
        $machine_id = input("machine_id");
        return $this->waitRemoteActionLogResult($machine_id, "pickUpDoorClose");
    }

    // public function remoteTakePhotos(){
    //     $sod_id = input('sod_id');
    //     $machine_id = input("machine_id");
    //     $refund_photo = $this->getSaleOrdersDetailsColumn(['sod_id' => $sod_id], 'refund_photo');
    //     if (!$refund_photo) {
    //         $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], "takePhotos", ['sod_id' => $sod_id]);
    //         return is_object($result) ? returnState(200,'正在从机器端获取拍照文件，请稍做等待后下载',$result) :
    //         $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
    //     }
    //     return returnState(200,'查询成功',$refund_photo);
    // }

    public function getRecycGoods(){
        $machine_id = input("machine_id");
        $sod_id = input("sod_id");
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], "recycGoods", ['sod_id' => $sod_id]);
        return is_object($result) ? $result : $this->app->machine->rFail($this->app->machine->lang("VMachine." . $result));
    }

    /**
     * 通过 remote_action_log 等待设备动作回执。
     * 下发前先创建日志，设备回执后按 log_id 更新 status，再轮询该日志状态返回结果。
     * @param string $machine_id
     * @param string $msgType
     * @return array|string
     */
    protected function waitRemoteActionLogResult($machine_id, $msgType)
    {
        if (!$machine_id) return returnValidate(lang("VMachine.machine_id_require"));
        $logId = $this->app->machine->addRALog([
            'machine_id' => $machine_id,
            'type' => $msgType,
            'status' => 1,
            'manager_id' => $this->manager['manager_id'] ?? 0,
            'operator_at' => date('Y-m-d H:i:s'),
        ]);
        $result = $this->app->machine->sendToMachine(['machine_id' => $machine_id], $msgType, ['log_id' => $logId]);
        if (!is_object($result)) {
            $this->app->machine->updateRALog(
                ['status' => 4, 'operator_at' => date('Y-m-d H:i:s')],
                ['id' => $logId],
                ['status', 'operator_at']
            );
            $msg = $result ? $this->app->machine->lang("VMachine." . $result) : $this->app->machine->lang("VMachine.machine_no_data");
            return $this->app->machine->rFail($msg);
        }

        $n = 0;
        $overtime = 20;
        while (1) {
            $log = $this->app->machine->getRALogsFind(['id' => $logId], 'id,machine_id,type,status,operator_at');
            if ($log) {
                $log = is_object($log) ? $log->toArray() : $log;
                if (intval($log['status']) === 3) {
                    return returnState(200, lang("query_success"), $log);
                }
                if (intval($log['status']) === 4) {
                    return returnState(100, lang("action_fail"), $log);
                }
            }
            sleep(1);
            $n++;
            if ($n >= $overtime) {
                return returnState(300, lang("VMachine.pick_up_door_overtime"), ['log_id' => $logId]);
            }
        }
    }
    
    public function exportEmptyChannel(){
        $postData = input();
        $where = $this->getWhere($postData, false, ["version" => "like","machine_name" => "like"]);
        return $this->app->machineChannel->exportEmptyList($where);
    }
    public function exportBadChannel(){
        $postData = input();
        $where = $this->getWhere($postData, false, ["version" => "like","machine_name" => "like"]);
        return $this->app->machineChannel->exportBadList($where);
    }
    public function exportStockOutChannel(){
        $postData = input();
        $where = $this->getWhere($postData, false, ["version" => "like","machine_name" => "like"]);
        return $this->app->machineChannel->exportStockOutList($where);
    }

    /**
     * 导出设备货道库存明细
     * @return array|string
     */
    public function exportStockRatio()
    {
        $mId = input('m_id');
        if (!$mId) {
            return returnValidate(lang("VMachine.machine_id_require"));
        }
        return $this->app->machineChannel->exportStockRatioByMachine($mId);
    }

    /**
     * 根据 street 回填设备省市区编码
     * @return array|string
     */
    public function repairAddressAreaIds()
    {
        $postData = input();
        return $this->app->machine->repairAddressAreaIds($postData);
    }
    
    /**
     * 设备销售额/销量统计图表
     * 数据格式与management/index/getSaleChart一致，筛选条件与设备列表一致
     * @return array|string
     */
    public function getSaleChart()
    {
        $postData = input();
        $type = $postData['type'] ?? 1;
        $ignoreFilterKeys = ['page', 'pageNum', 'version_sort', 'stock_ratio', 'sort_name', 'sort_order', 'type'];
        $hasMachineFilter = false;
        foreach ($postData as $key => $value) {
            if (in_array($key, $ignoreFilterKeys)) {
                continue;
            }
            if ($value !== '' && $value !== null) {
                $hasMachineFilter = true;
                break;
            }
        }
        if (!$hasMachineFilter) {
            $where = $this->getWhere([]);
            return $this->app->saleOrders->getChartData($where, $type);
        }

        $machineIds = [];
        if (isset($postData['machine_group_id']) && $postData['machine_group_id']) {
            $machineIds = $this->app->machine->getMachineGroupMgColumn(['mg_id' => $postData['machine_group_id']], 'machine_id');
            unset($postData['machine_group_id']);
            if (!$machineIds) return $this->app->machine->rNoData();
        }
        if (isset($postData['online_all'])) {
            $postData['online'] = $postData['online_all'];
            unset($postData['online_all']);
        }
        $isOnOff = $postData['is_on_off'] ?? 0;
        unset($postData['pageNum'], $postData['version_sort'], $postData['stock_ratio'], $postData['sort_name'], $postData['sort_order'], $postData['is_on_off'], $postData['type']);

        $onlineValue = null;
        if (isset($postData['online']) && $postData['online'] !== '') {
            $onlineValue = $postData['online'];
            unset($postData['online']);
        }

        $where = $this->getWhere($postData, false, ["machine_name" => "like"]);
        $where[] = ['vending_machine_type', '=', 1];
        if (!empty($machineIds)) $where[] = ['machine_id', 'in', $machineIds];

        if ($isOnOff) {
            $where[] = $this->buildIsOnOffWhere($isOnOff);
        }

        if ($onlineValue !== null) {
            if ($onlineValue == 1) {
                $where['raw'] = (isset($where['raw']) ? $where['raw'] . ' AND ' : '') . '(a.http_online = 1 OR a.online = 1)';
            } else {
                $where[] = ['http_online', '=', 2];
                $where[] = ['online', '=', 2];
            }
        }

        // 权限过滤
        $permitted = $this->app->machine->resolvePermittedMachineIds();
        if ($permitted !== null) {
            $where[] = ['m_id', 'in', $permitted];
        }

        // 查出符合条件的设备 m_id
        $machineList = MachineModel::getList($where, 0, 'm_id');
        $mIds = [];
        if ($machineList) {
            foreach ($machineList as $item) {
                $itemArr = is_object($item) ? $item->toArray() : $item;
                $mIds[] = $itemArr['m_id'];
            }
        }
        if (empty($mIds)) return $this->app->saleOrders->rNoData();

        $chartWhere = $this->getWhere([]);
        $chartWhere[] = ['m_id', 'in', $mIds];
        return $this->app->saleOrders->getChartData($chartWhere, $type);
    }

    /**
     * 下发机头遗留商品处理指令。
     * msgType：11-继续出货，12-直接回收。
     *
     * 设备后续统一通过 remoteOutGoods 上报，并原样返回 sod_id、log_id。
     * @return array|string
     */
    public function sendMainControlV2()
    {
        $actionLogId = 0;
        $sodId = 0;
        $previousRemoteOutGoodsStatus = 0;
        $continueRemoteStatusMarked = false;
        try {
            $postData = input();
            $msgType = intval($postData['msgType'] ?? 0);
            $typeList = [
                11 => 'continueOutGoods',
                12 => 'recycGoods',
            ];
            if (!isset($typeList[$msgType])) {
                return returnValidate(lang('VMachine.msg_type_invalid'));
            }

            $machineId = trim((string)($postData['machine_id'] ?? ''));
            if ($machineId === '') return returnValidate(lang('VMachine.machine_id_require'));

            $machineLevel = MachineModel::where('machine_id', $machineId)->value('machine_level');
            if ($machineLevel === null) return returnValidate(lang('VMachine.machine_no_data'));
            if (intval($machineLevel) !== 2) {
                return returnValidate(lang('VMachine.luxury_command_only'));
            }

            $sodId = intval($postData['sod_id'] ?? 0);
            if ($sodId <= 0) return returnValidate('sod_id不能为空');

            $detail = $this->getSaleOrdersDetailsFind(
                ['sod_id' => $sodId],
                'sod_id,order_id,g_id,channel_code,quantity,success_quantity,fail_quantity,refund_quantity,remote_out_goods_status'
            );
            if (!$detail) return returnValidate('找不到订单子单记录');
            $detail = is_object($detail) ? $detail->toArray() : $detail;

            $order = $this->getSaleOrdersFind(
                ['order_id' => $detail['order_id']],
                'order_id,trade_no,machine_id,out_status,refund_status,refund_quantity'
            );
            if (!$order) return returnValidate('找不到订单记录');
            $order = is_object($order) ? $order->toArray() : $order;

            if ((string)$order['machine_id'] !== $machineId) {
                return returnValidate('子单不属于当前设备');
            }
            if (intval($order['out_status']) !== 5) {
                return returnValidate('仅出货失败订单允许继续出货或直接回收');
            }
            if (intval($detail['quantity']) !== 1
                || intval($detail['success_quantity']) !== 1
                || intval($detail['fail_quantity']) !== 0) {
                return returnValidate('该子单不是商品已离开货道、后续动作失败的状态');
            }
            if ($msgType === 11 && in_array(intval($detail['remote_out_goods_status'] ?? 0), [1, 2, 21, 3], true)) {
                return returnValidate('该子单正在远程出货或已经远程出货成功，不能重复继续出货');
            }

            $lastAction = $this->app->machine->getRALogsFind([
                ['sod_id', '=', $sodId],
                ['type', 'in', ['continueOutGoods', 'recycGoods']],
            ], 'id,type,status', 'id desc');
            if ($lastAction) {
                $lastAction = is_object($lastAction) ? $lastAction->toArray() : $lastAction;
                $lastStatus = intval($lastAction['status'] ?? 0);
                if (in_array($lastStatus, [1, 2], true)) {
                    return returnValidate('该子单已有正在执行的机头商品处理指令');
                }
                if ($lastStatus === 3) {
                    return returnValidate('该子单的机头商品已经处理成功，不能重复操作');
                }
            }

            $resolvedMsgType = $typeList[$msgType];
            $actionLogId = $this->app->machine->addRALog([
                'machine_id' => $machineId,
                'type' => $resolvedMsgType,
                'msgType' => $resolvedMsgType,
                'order_id' => $order['order_id'],
                'sod_id' => $sodId,
                'goods_id' => intval($detail['g_id'] ?? 0),
                'channel_code' => $detail['channel_code'] ?? '',
                'status' => 1,
                'manager_id' => $this->manager['manager_id'] ?? 0,
                'operator_at' => date('Y-m-d H:i:s'),
            ]);
            if (!$actionLogId) return returnValidate('创建远程动作日志失败');

            if ($msgType === 11) {
                $previousRemoteOutGoodsStatus = intval($detail['remote_out_goods_status'] ?? 0);
                $markResult = $this->updateSaleOrdersDetails(
                    ['sod_id' => $sodId, 'remote_out_goods_status' => 1],
                    [],
                    ['remote_out_goods_status']
                );
                if ($markResult === false) {
                    $this->app->machine->updateRALog(
                        ['status' => 4, 'operator_at' => date('Y-m-d H:i:s')],
                        ['id' => $actionLogId],
                        ['status', 'operator_at']
                    );
                    return returnValidate('更新子单远程出货状态失败');
                }
                $continueRemoteStatusMarked = true;
            }

            $otherData = [
                'time_point' => !empty($postData['time_point']) ? strtotime($postData['time_point']) : time(),
                'sod_id' => $sodId,
                'log_id' => $actionLogId,
                'trade_no' => $order['trade_no'] ?? '',
            ];
            $result = $this->app->machine->sendToMachine(
                ['machine_id' => $machineId],
                $resolvedMsgType,
                $otherData
            );
            if (!is_object($result)) {
                $this->app->machine->updateRALog(
                    ['status' => 4, 'operator_at' => date('Y-m-d H:i:s')],
                    ['id' => $actionLogId],
                    ['status', 'operator_at']
                );
                if ($continueRemoteStatusMarked) {
                    $this->updateSaleOrdersDetails(
                        ['sod_id' => $sodId, 'remote_out_goods_status' => $previousRemoteOutGoodsStatus],
                        [],
                        ['remote_out_goods_status']
                    );
                    $continueRemoteStatusMarked = false;
                }
            }
            return is_object($result)
                ? $result
                : $this->app->machine->rFail($this->app->machine->lang('VMachine.' . $result));
        } catch (\Exception $e) {
            if ($continueRemoteStatusMarked && $sodId) {
                try {
                    $this->updateSaleOrdersDetails(
                        ['sod_id' => $sodId, 'remote_out_goods_status' => $previousRemoteOutGoodsStatus],
                        [],
                        ['remote_out_goods_status']
                    );
                } catch (\Throwable $statusException) {
                    actionException($statusException, 1, 'headGoodsAction');
                }
            }
            if ($actionLogId) {
                try {
                    $this->app->machine->updateRALog(
                        ['status' => 4, 'operator_at' => date('Y-m-d H:i:s')],
                        ['id' => $actionLogId],
                        ['status', 'operator_at']
                    );
                } catch (\Throwable $logException) {
                    actionException($logException, 1, 'headGoodsAction');
                }
            }
            actionException($e, 1);
            return $this->app->machine->rTryCatch($e->getMessage());
        }
    }
}
