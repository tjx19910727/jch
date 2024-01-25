<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/28
 * Time: 10:32
 */

namespace app\AppFactory\Kernel\Traits\GatewayWorker;


use app\AppFactory\Kernel\Support\Validate\GatewayWorker\VTerminal;

trait TerminalTrait
{
    /**
     * 通讯心跳
     * @param $data
     * @return mixed
     */
    public function heartbeat()
    {
        $updateMachine['last_online_time'] = time();
        $updateMachine['online'] = 1;
        $this->updateMachine($updateMachine, ['machine_id' => $this->message['machine_id']]);
        $details = $this->getStoreOnlineDetailsFind(['terminal_no' => $this->message['terminal_no'], 'offline_time' => 0], '*', 'sod_id asc');
        if ($details) {
            $update = [
                'sod_id' => $details['sod_id'],
                'heart_time' => time()
            ];
            // 在线时长跨天，自动结束昨天的记录，生成今天的新上线记录
            $today = strtotime(date("Y-m-d"));
            if ($today != $details['d_date']) {
                $update['offline_time'] = bcadd(bcadd($today, 86400), HourMinuteSec2int("23:59:59"));
                $update['sod_duration'] = bcsub($update['offline_time'], $details['online_time']);
                $this->store['heart_time'] = $today;
                $this->onlineDetails();
            }
            $this->updateStoreOnlineDetails($update);
        }
        if (!$details) {
            $this->onlineDetails();
        }
        $return = $this->rSuccess();
        return $return;
    }

    /**
     * 注册上线
     * @return mixed
     */
    public function login()
    {
        $this->initStoreDoorLock();
        // 查门店
        $this->store['client_id'] = $this->client_id;
        $this->store['heart_time'] = time();
        $this->store['online'] = 1;
        $this->updateStore($this->store, [], ['heart_time', 'online', 'client_id']);
        $details = $this->getStoreOnlineDetailsFind(['terminal_no' => $this->message['terminal_no'], 'offline_time' => 0], 'sod_id', 'sod_id asc');
        if (!$details) $this->onlineDetails();

        // 查硬件列表
        $hardware = $this->getStoreHardwareList(['store_id' => $this->store['store_id'], 'status' => 1], 0,
            'sh_id,hardware_name,hardware_number,hardware_type');
        $this->store['hardware'] = $hardware;
        // 查语音列表
        $voice = $this->getStoreVoiceList(['store_id' => $this->store['store_id'], 'status' => 1], 0, 'sv_id,title,file_path,type,status');
        $this->store['voice'] = $voice;


        $return = $this->rQ($this->store);
        // 发送上线通知
        @$this->sendOnOfflineNotice($this->store);
        return $return;
    }

    /**
     * 初始化门店门、锁状态
     */
    public function initStoreDoorLock()
    {
        $this->store['lock_status'] = $this->message['data']['lock_status'] ?? 3;
        $this->store['door_status'] = $this->message['data']['door_status'] ?? 3;
        $this->sendGatewaySwitch = 0;
        $this->updateStore($this->store);
    }

    /**
     * 生成上线记录
     * @param $store
     */
    public function onlineDetails()
    {
        $insert = [
            "store_id" => $this->store['store_id'],
            "store_name" => $this->store['store_name'],
            "terminal_no" => $this->store['terminal_no'],
            "client_id" => $this->store['client_id'],
            "online_time" => $this->store['heart_time'],
            "d_date" => strtotime(date("Y-m-d")),
            "heart_time" => time(),
        ];
        $this->addStoreOnlineDetails($insert);
    }

    /**
     * 信号值上报
     * @return mixed
     */
    public function signal()
    {
        try {
            validate(VTerminal::class)->scene($this->message['msgType'])->check($this->message['data']);
            $return = $this->rSuccess();
            if ($this->store['terminal_signal'] != $this->message['data']['signal']) {
                $update['store_id'] = $this->store['store_id'];
                $update['terminal_signal'] = $this->message['data']['signal'];
                $result = $this->updateStore($update, [], ['store_id', "terminal_signal"]);
                $return = $this->rAction($result);
            }
        } catch (\Exception $e) {
            $return = $this->rValidate($e->getMessage());
        }
        return $return;
    }

    /**
     * 开门上报
     * @param $data
     * @return mixed
     */
//    public function openDoor()
//    {
//        $record = $this->message['data'];
//        $store = $this->getStoreFind(['terminal_no' => $this->message['terminal_no']]);
//        if (!$store) $return = $this->rFail("查无门店信息");
//        // 硬件为锁类型，记录内容为进店和离店
//        if ($store) {
//            if (!in_array($record['record_type'], [1, 2])) $return = $this->rFail("记录类型不在范围内");
//            if (in_array($record['record_type'], [1, 2])) {
//                $sh = $this->getStoreHardwareFind(['sh_id' => $record['sh_id']]);
//                if (!$sh) $return = $this->rFail("查无门锁信息");
//                if ($sh) {
//                    if ($sh['status'] == 2) $return = $this->rFail("硬件已被禁用");
//                    if ($sh['status'] == 1) {
//                        if (!in_array($sh['hardware_type'], [1, 2])) $return = $this->rFail("硬件类型不是门锁");
//                        if (in_array($sh['hardware_type'], [1, 2])) {
//                            $this->updateStoreVisit(['sh_id' => $record['sh_id'], 'door_status' => 2]);
//                            $insert = [
//                                "store_id" => $store['store_id'],
//                                "store_name" => $store['store_name'],
//                                "terminal_no" => $store['terminal_no'],
//                                "unlock_id" => $record['unlock_id'] ?? 0,
//                                "action_time" => time(),
//                                "record_type" => $record['record_type'],
//                                "sh_id" => $record['sh_id'],
//                                "hardware_number" => $sh['hardware_number'],
//                                "hardware_name" => $sh['hardware_name'],
//                                "hardware_type" => $sh['hardware_type'],
//                            ];
//                            $visit_id = $this->addStoreVisit($insert);
//                            $return = $this->rA($visit_id);
//
//                            // 开门通知
//                            @$this->sendOpenDoorNotice($store);
//                        }
//                    }
//                }
//            }
//        }
//        return $return;
//    }

    /**
     * 关门上报
     * @return mixed
     */
//    public function closeDoor()
//    {
//        $where['terminal_no'] = $this->message['terminal_no'];
//        $where['door_status'] = 1;
//        $visit = $this->getStoreVisitFind($where, 'visit_id,door_status', 'visit_id asc');
//        if (!$visit) return $this->rFail("查无开门信息");
//        $visit = $visit->toArray();
//        $visit['door_status'] = 2;
//        $result = $this->updateStoreVisit($visit);
//        return $this->rAction($result);
//
//    }

    /**
     *  触发onClose断线，原因可能为断网、网络差、丢包、主动断开或其他
     * @param array $data
     */
    public function onClose()
    {
        $store = $this->getStoreFind(['client_id' => $this->client_id], 'store_id,terminal_no');
        if ($store) {
            $where['store_id'] = $store['store_id'];
            $this->offlineDetails($store);

            // 发送离线通知
            @$this->sendOnOfflineNotice($store);
        } else {
            $where['client_id'] = $this->client_id;
        }
        $this->updateStore(['heart_time' => time(), 'online' => 2], $where, ['heart_time', 'online']);
    }

    /**
     * 增加离线时间记录
     * @param $store
     */
    public function offlineDetails($store)
    {
        $details = $this->getStoreOnlineDetailsFind(['store_id' => $store['store_id'], 'offline_time' => 0], "*", "sod_id asc");
        if ($details) {
            $update['sod_id'] = $details['sod_id'];
            $update['offline_time'] = time();
            $update['heart_time'] = time();
            $update['sod_duration'] = bcsub(time(), $details['online_time']);
            $this->updateStoreOnlineDetails($update);
        }
    }

    /**
     * 设备上报查询货架数据
     * @return mixed
     */
    public function queryStoreShelves()
    {
        $subData = $this->message['data'];
        $where['store_id'] = $subData['store_id'];
        if (isset($subData['bar_code']) && $subData['bar_code']) {
            $where['bar_code'] = $subData['bar_code'];
        }
        $where['shelves_status'] = 1;
        $field = "ss_id,store_id,store_name,shelves_number,goods_id,wg_id,goods_name,goods_c_id,goods_c_name,goods_pic,cost_price,
        retail_price,stock,frozen_stock,bar_code,batch_number,manufacture_time,sell_by_date,('" . date("Y-m-d H:i:s") . "') queryTime";
        $shelves = $this->getStoreShelvesList($where, 0, $field, 'shelves_number desc');
        $shelves = $shelves->toArray();
        if ($shelves) {
            $shelves = obj2arr($shelves);
            $carData = $this->getCache("store_" . $this->message['terminal_no'] . "_BuyCar");
            $carData = obj2arr($carData);
            if ($carData) $carData = array_merge($carData,$shelves);
            if (!$carData) $carData = $shelves;
            $this->setCache("store_" . $this->message['terminal_no'] . "_BuyCar",$carData);
            $this->sendGatewayGroup("store" . $subData['store_id'],$this->r(200,'终端查询的商品',$shelves),'terminalQueryShelves');
        }
        $return = $this->rQ($shelves);
        return $return;
    }

    /**
     * 断电上报
     * @return mixed
     */
    public function outage()
    {
        @$this->sendOutageNotice($this->store);
        return $this->r(200, '处理完成');
    }

    /**
     * 开门请求上报
     * @return mixed
     */
    public function openDoorRequest()
    {
        @$this->sendOpenRequestNotice($this->store);
        return $this->r(200, '处理完成');
    }
}