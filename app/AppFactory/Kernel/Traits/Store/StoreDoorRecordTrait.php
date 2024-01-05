<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/9/21
 * Time: 9:21
 */

namespace app\AppFactory\Kernel\Traits\Store;


use app\AppFactory\Kernel\Model\Store\StoreDoorRecordModel;

trait StoreDoorRecordTrait
{

    public function getStoreDoorRecordFind($where,$field = "*", $order = "")
    {
        return StoreDoorRecordModel::getFind($where,$field,$order);
    }

    public function getStoreDoorRecordList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return StoreDoorRecordModel::getList($where,$pageNum,$field,$order);
    }

    public function addStoreDoorRecord($insert)
    {
        $sd = StoreDoorRecordModel::create($insert);
        return $sd->sd_id;
    }

    public function updateStoreDoorRecord($update,$where = [], $field = [])
    {
        return StoreDoorRecordModel::update($update,$where,$field);
    }

    /**
     * 上报门状态，开门状态：1.成功，2.失败，关门状态：1.成功，2.失败
     * terminal_no
     * msgType:doorStatusReport
     * data:["type":1|2,"status":1]
     * @return mixed
     */
    public function doorStatusReport()
    {
        // 开门类型上报数据
        //      查询180秒内有没有进店未确认开门记录，有则修改进店开门记录状态，生成开门到访记录
        //      查无开门记录则默认为离店开门
        //          查询最后一条开锁记录，记录应由下发开锁命令时生成，修改开锁状态也是由锁状态上报方法处理
        //          查无开锁记录时，生成一条已确认成功的开锁记录。
        //          整理离店开门记录数据，生成一条离店开门确认数据
        //      修改门店门状态字段为已开门
        // 关门类型上报数据
        //      查询最后一条未确认关门状态的记录，有则修改关门结果
        //      查无数据时略过
        //      执行生成到访记录流程
        //      修改门店门状态字段为已关门


        $updateStore['store_id'] = $this->store['store_id'];
        // 开门
        if ($this->message['data']['type'] == 1) {
            $openResult = $this->openDoor();
            if ($openResult !== true) return $openResult;
            // 修改门店门状态字段为已开门
            $updateStore['door_status'] = 1;
        }
        // 关门
        if ($this->message['data']['type'] == 2) {
            $closeResult = $this->closeDoor();
            if ($closeResult !== true) return $closeResult;
            // 修改门店门状态字段为已关门
            $updateStore['door_status'] = 2;
        }
        $this->updateStore($updateStore);
        return $this->r(200,'上报门状态处理成功');
    }

    /**
     * 开门处理
     * @param $store
     * @return bool
     */
    public function openDoor()
    {
        $where['terminal_no'] = $this->message['terminal_no'];
        $where['type'] = 1;
        $where['open_status'] = 3;
//        $where[] = ['create_time', '>', time() - 180];
        // 查询门店180秒内未确认进店开门记录，查无信息则全部归类到离店
        $open = $this->getStoreDoorRecordFind($where, '*', 'sd_id desc');
        $open = obj2arr($open);
        actionLog($this->getLS(),'查询门店未确认进店开门记录');
        actionLog($open,'查询门店未确认进店开门记录');
        if ($open) {
            // 修改开门状态及时间
            $updateDoor["open_status"] = $this->message['data']['status'];
            $updateDoor["open_time"] = time();
            $updateDoor['sd_id'] = $open['sd_id'];
            $doorResult = $this->updateStoreDoorRecord($updateDoor);
            if (!$doorResult)   return $this->r(100, '进店开门记录修改失败');
            $visit = $this->getStoreVisitFind(['store_id' => $this->store['store_id'],'end_time' => 0]);
            if (!$visit) {
                // 生成门店到访记录
                $insertVisit = [
                    "store_id" => $this->store['store_id'],
                    "store_name" => $this->store['store_name'],
                    "terminal_no" => $this->message['terminal_no'],
                    "start_time" => time(),
                ];
                $addVisitResult = $this->addStoreVisit($insertVisit);
                if (!$addVisitResult) return $this->r(100, '生成到访记录失败');
            }
            // 发送给终端触发语音
            $voice = $this->getStoreVoiceFind(['store_id' => $this->store['store_id'],"status" => 1,'type' => 2],'sv_id,store_id,store_name,terminal_no,title,file_path,type,status','sv_id desc');
            $this->sendGateway($this->message['terminal_no'],$this->r(200,'进店开门触发语音',$voice),'playVoice');
            // 获取店长、值守人员信息
            // 发送给值守人员信息提示有人进店
            $this->sendGatewayGroup("store" . $this->store['store_id'],$this->r(200,'有人进店了',['store_id' => $this->store['store_id'],'store_name' => $this->store['store_name']]),'someoneEnter');

        } else {
            // 离店开门
            // 最近一条上锁记录
            $sl = $this->getStoreLockRecordFind(['store_id' => $this->store['store_id'], 'type' => '1', 'status' => 1], '*', 'update_time desc');
            // 查无开锁记录时，生成一条已确认成功的开锁记录。
            if (!$sl) {
                // 查门锁硬件
                $sh = $this->getStoreHardwareFind(['store_id' => $this->store['store_id'], 'hardware_type' => 1, 'status' => 1], 'sh_id,hardware_number');
                if (!$sh) return $this->r(100, '查无电子锁硬件');
                $insertLock = [
                    "store_id" => $this->store['store_id'],
                    "store_name" => $this->store['store_name'],
                    "terminal_no" => $this->store['terminal_no'],
                    "sh_id" => $sh['sh_id'],
                    "hardware_number" => $sh['hardware_number'],
                    "send_type" => 3,
                    "type" => 1,
                    "status" => $this->message['data']['status'],
                ];
                $addResult = $this->addStoreLockRecord($insertLock);
                if (!$addResult) return $this->r(100, '添加开锁记录失败');
            }
            // 查询未确定离店开门记录，有则修改开门状态与时间，无则新生成开门记录
            $outRecord = $this->getStoreDoorRecordFind(['store_id' => $this->store['store_id'],'type' => 2,'open_status' => 3]);
            $outRecord = obj2arr($outRecord);
            if ($outRecord) {
                $updateDoor["open_status"] = $this->message['data']['status'];
                $updateDoor["open_time"] = time();
                $updateDoor['sd_id'] = $outRecord['sd_id'];
                $outResult = $this->updateStoreDoorRecord($updateDoor);
                if (!$outResult)   return $this->r(100, '离店开门记录修改失败');
            } else {
                // 整理离店开门记录数据，生成一条离店开门确认数据
                $insertDoor = [
                    "store_id" => $this->store['store_id'],
                    "store_name" => $this->store['store_name'],
                    "terminal_no" => $this->message['terminal_no'],
                    "type" => 2,
                    "open_status" => $this->message['data']['status'],
                    'open_time' => time(),
                ];
                $insertDoorResult = $this->addStoreDoorRecord($insertDoor);
                if (!$insertDoorResult) return $this->r(100, '生成离店开门数据失败');
            }
            $voice = $this->getStoreVoiceFind(['store_id' => $this->store['store_id'],"status" => 1,'type' => 3],'sv_id,store_id,store_name,terminal_no,title,file_path,type,status','sv_id desc');
            $this->sendGateway($this->message['terminal_no'],$this->r(200,'离店开门触发语音',$voice),'playVoice');
        }
        return true;
    }

    /**
     * 关门处理
     * @param $store
     * @return bool
     */
    public function closeDoor()
    {
        $where['terminal_no'] = $this->message['terminal_no'];
        $where['close_status'] = 3;
//        $where[] = ['create_time', '>', time() - 180];
        // 查询门店180秒内未确认关门记录
        $close = $this->getStoreDoorRecordFind($where, '*', 'sd_id desc');
        $close = obj2arr($close);
        if ($close) {
            $updateClose['sd_id'] = $close['sd_id'];
            $updateClose['close_status'] = 1;
            $updateClose['close_time'] = time();
            $this->updateStoreDoorRecord($updateClose);
            // 当type为离店关门时，才处理到访记录
            if ($close['type'] == 2) {
                // 查询最后一条到访记录，修改到访结束时间，统计最新时长
                $whereVisit['store_id'] = $this->store['store_id'];
                $visit = $this->getStoreVisitFind($whereVisit, 'visit_id,start_time,end_time,duration', 'visit_id desc');
                $visit = obj2arr($visit);
                actionLog($this->getLS(), '查询最后一条没有结束时间的到访记录');
                actionLog($visit, '到访记录');
                if ($visit) {
                    $updateVisit['visit_id'] = $visit['visit_id'];
                    // 有离店时间，时长 = 当前时间 - 离店时间 + 之前的时长
                    $duration = time() - $visit['end_time'] + $visit['duration'];
                    // 离店时间不是当前时间的，时长为当前时间减有进店时间
                    if ($visit['end_time'] != time()) {
                        $updateVisit['end_time'] = time();
                        $duration = time() - $visit['start_time'];
                    }

                    // 到访时长 = 当前时间 - 到访记录结束时间 + 已记录的秒数（默认0）
                    $updateVisit['duration'] = $duration;
                    $updateVisitResult = $this->updateStoreVisit($updateVisit);
                    if (!$updateVisitResult) return $this->r(100, '修改到访记录失败');
                } else {
                    // 没有到访记录的，生成一条当前时间为进店与离店时间的异常到访记录
                    $insert = [
                        "store_id" => $this->store['store_id'],
                        "store_name" => $this->store['store_name'],
                        "terminal_no" => $this->store['terminal_no'],
                        "start_time" => time(),
                        "end_time" => time(),
                        "duration" => 0,
                    ];
                    $this->addStoreVisit($insert);
                }
            }
        } else {
            // 查无开门记录的，生成新的门开关记录，开关门都是当前时间点
            $insertDoorRecord = [
                "store_id" => $this->store['store_id'],
                "store_name" => $this->store['store_name'],
                "terminal_no" => $this->store['terminal_no'],
                "type" => 2,
                "open_status" => 1,
                "close_status" => 1,
                "open_time" => time(),
                "close_time" => time(),
            ];
            $this->addStoreDoorRecord($insertDoorRecord);

            // 查最后到访记录
            $whereVisit['store_id'] = $this->store['store_id'];
            $visit = $this->getStoreVisitFind($whereVisit, 'visit_id,start_time,end_time,duration', 'visit_id desc');
            $visit = obj2arr($visit);
            if ($visit) {
                $updateVisit['visit_id'] = $visit['visit_id'];
                // 有离店时间，时长 = 当前时间 - 离店时间 + 之前的时长
                $duration = time() - $visit['end_time'] + $visit['duration'];
                // 离店时间不是当前时间的，时长为当前时间减有进店时间
                if ($visit['end_time'] != time()) {
                    $updateVisit['end_time'] = time();
                    $duration = time() - $visit['start_time'];
                }

                // 到访时长 = 当前时间 - 到访记录结束时间 + 已记录的秒数（默认0）
                $updateVisit['duration'] = $duration;
                $updateVisitResult = $this->updateStoreVisit($updateVisit);
                if (!$updateVisitResult) return $this->r(100, '修改到访记录失败');
            } else {
                // 没有到访记录的，生成一条当前时间为进店与离店时间的异常到访记录
                $insert = [
                    "store_id" => $this->store['store_id'],
                    "store_name" => $this->store['store_name'],
                    "terminal_no" => $this->store['terminal_no'],
                    "start_time" => time(),
                    "end_time" => time(),
                    "duration" => 0,
                ];
                $this->addStoreVisit($insert);
            }
        }
        return true;
    }


    /**
     * 添加未确定门记录
     * @param $store
     * @return array|string
     */
    protected function addDoorRecord($store)
    {
        $inType = $store['inType'] ?? session("inType");
        $data = [
            "store_id" => $store['store_id'],
            "store_name" => $store['store_name'],
            "terminal_no" => $store['terminal_no'],
            "type" => $inType,
        ];
        $door = $this->addStoreDoorRecord($data);
        return $door ? $this->r(200,'添加成功') : $this->r(100,'添加门记录失败');
    }
}