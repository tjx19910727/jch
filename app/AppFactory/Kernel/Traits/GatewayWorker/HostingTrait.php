<?php
/**
 * 托管频道处理
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/9/26
 * Time: 16:37
 */

namespace app\AppFactory\Kernel\Traits\GatewayWorker;



trait HostingTrait
{
    public function join()
    {
        $hostingArr = $this->getCache(CacheName);
        return $this->r(200,'加入托管频道成功',$hostingArr);
    }

    public function terminalBuyCar()
    {
        $this->message['store_id'];
        $terminal_no = $this->getStoreValue(['store_id' => $this->message['store_id']],'terminal_no');
        $shelves = $this->getCache("store_" . $terminal_no . "_BuyCar");
        $this->sendGateway($this->message['uid'],$this->r(200,'查询成功',$shelves),'getTerminalBuyCar');
    }

    /**
     * 门店托管申请上报
     * @return mixed
     */
    public function handleRequestHosting()
    {
        $hosting = $this->getStoreHostingFind(['store_id' => $this->store['store_id'],['status',"between",[1,2]]],'id');
        if ($hosting) return $this->r(100,'您的门店当前有未完成的托管操作，请先结束后再重新申请');
        $bindStrategy = $this->getStrategyStoreFind(['store_id' => $this->store['store_id'],'s_type' => 6],'*','sort desc');
        if (!$bindStrategy) return $this->r(100,'查无绑定托管策略');
        $strategy = $this->getStrategyHostingFind(['st_id' => $bindStrategy['s_id']],'*');
        if (!$strategy) return $this->r(100,'查无托管策略');
        if ($strategy['status'] != 1) return $this->r(100,'该托管策略已禁用');
        $hosting = [
            "store_id" => $this->store['store_id'],
            "store_name" => $this->store['store_name'],
            "store_manager" => $this->store['store_manager'],
            "store_manager_name" => $this->getAuthManagerValue(['manager_id' => $this->store['store_manager']],'nickname'),
            "st_id" => $bindStrategy['s_id'],
            "terminal_no" => $this->store['terminal_no'],
            "charge_type" => $strategy['charge_type'],
            "charge_value" => $strategy['charge_value'],
            "charge_max_limit" => $strategy['charge_max_limit'],
            "cycle" => $strategy['cycle'],
        ];
        $hosting['id'] = $this->addStoreHosting($hosting);
        if ($hosting['id']) {
            $hosting = $this->getStoreHostingFind($hosting);
            $this->moveInHostingArr($hosting);
            return $this->r(200,'托管申请上报成功，等待工作人员接受托管',['hosting' => $hosting]);
        }
        return $this->r(100,'托管申请上报失败');
    }

    /**
     * 取消托管，已托管的记录，在每一次交易时已自动计算生成详情记录，在此不做任何金额上的修改操作。
     * @return mixed
     */
    public function handleCancel($hosting)
    {
        $updateHosting['id'] = $hosting['id'];
        $updateHosting['status'] = 4;
        // 托管已被接受
        if ($hosting['status'] == 2) {
            $updateHosting['status'] = 3;
        }
        $flag[] = $this->updateStoreHosting($updateHosting);
        if ($this->store['store_mode'] == 3) $flag[] = $this->updateStore(['store_id' => $this->store['store_id'],'store_mode' => 2]);
        $result = flag_check($flag);
        return $result;
    }

    /**
     * 接受托管
     * @return mixed
     */
    public function handleAccept()
    {
        $updateHosting = [
            "id" => $this->message['data']['id'],
            "start_time" => time(),
            "status" => 2,
            "watch_man" => $this->message['uid'],
            "watch_nickname" => $this->watchman['nickname'],
        ];
        $flag[] = $this->updateStoreHosting($updateHosting);
        if ($this->store['store_mode'] != 3) {
            $this->store['store_mode'] = 3;
            $updateStore['store_id'] = $this->store['store_id'];
            $updateStore['store_mode'] = 3;
            $flag[] = $this->updateStore($updateStore);
        }
        $result = flag_check($flag);
        return $result;
    }

    /**
     * 加入缓存
     * @param $hosting
     */
    public function moveInHostingArr($hosting)
    {
        $hostingArr = $this->getCache(CacheName);
        $hostingArr[] = $hosting;
        $this->setCache(CacheName,$hostingArr);
    }

    /**
     * 移出缓存
     * @param $hosting
     */
    public function moveOutHostingArr($hosting)
    {
        $temp = [];
        $hostingArr = $this->getCache(CacheName);
        if ($hostingArr) {
            foreach ($hostingArr as $value) {
                if ($value['id'] == $hosting['id']) continue;
                $temp[] = $value;
            }
        }
        $this->setCache(CacheName,$temp);
    }
}