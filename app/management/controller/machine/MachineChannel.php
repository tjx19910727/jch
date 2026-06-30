<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:56
 */

namespace app\management\controller\machine;


use app\AppFactory\AppFactory;
use app\management\controller\Common;
use app\management\validate\Machine\VMachineChannel;


class MachineChannel extends Common
{

    protected $field = "*";
    protected $validatePath = VMachineChannel::class;

    public function getList()
    {
        $postData = input();
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        //return $this->app->machineChannel->getList($where,$pageNum,$this->field);
        return $this->app->machineChannel->getMChannelList($where,$pageNum,$this->field,'',$hasCostPriceAuth);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineChannel->getMcFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineChannel->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineChannel->updateMc($postData);
    }

    /**
     * 锁定/解锁货道价格
     * @return array|\think\response\Json
     */
    public function lockPrice()
    {
        $postData = input();
        return $this->app->machineChannel->lockPrice($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineChannel->del($postData);
    }

    /**
     * 导出设备货架-SKU分组
     * @return mixed
     */
    public function exportSku()
    {
        $m_id = input("m_id");
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        return $this->app->machineChannel->exportMcSku($m_id, $hasCostPriceAuth);
    }

    /**
     * 导出设备货架
     * @return array|\think\response\Json
     */
    public function export()
    {
        $m_id = input("m_id");
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        return $this->app->machineChannel->exportMc($m_id, $hasCostPriceAuth);
    }

    /**
     * 获取设备货道槽位实时图片
     * @return array|string
     */
    public function getChannelImg()
    {
        $channel_code = input('channel_code');
        $machine_id = input('machine_id');
        if (!$machine_id) return returnState(100,lang("VMachineChannel.machine_id_require"));
        if (!$channel_code) return returnState(100,lang("VMachineChannel.channel_code_require"));
        $send = "";
        $n = 0;
        $where["channel_code"] = $channel_code;
        $where['machine_id'] = $machine_id;
        while(1) {
            $channelImg = $this->app->machineChannel->getMachineChannelValue($where,"channel_img");
            if ($channelImg) {
                $this->app->machineChannel->updateMachineChannel(["channel_img" => ""],$where);
                return returnState(200,lang("query_success"),$channelImg);
            }
            if (!$send) {
                $this->app->machine->sendToMachine(['machine_id' => $machine_id],"channelImg",['channel_code' => $channel_code]);
                $send = 1;
            }
            sleep(1);
            $n++;
            if ($n >= 50) {
                return returnState(100,lang("action_machine_overtime"));
            }
        }
    }
   
    
    //设置货道商品积分比例
    public function  setMCGiftPoints(){
        $m_id = input("m_id");
        $integral_rate = input('integral_rate') ?? 1;
        return $this->app->machineChannel->setMachineChannelGiftPoints($m_id, $integral_rate);
    }

    //设置微程商品在虚拟货道排序
    public function setWcGoods2Mc(){
        $m_id = input("m_id");
        $out_nos = input('out_nos');
        $out_nos_arr = explode(',', $out_nos);
        return $this->app->weicheng->setWcMachineChannelListsV2($m_id, $out_nos_arr);
    }

    public function getWcGoods2Mc()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['m_id' => 'like']);
        return $this->app->weicheng->getWcMachineChannelLists($where, $pageNum, "*", 'id desc');
    }

    /**
     * 虚拟货道排序操作日志列表
     */
    public function getSortLogList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['machine_ids' => 'like','m_ids' => 'like', 'operator_name' => 'like']);
        return $this->app->weicheng->getMcSortLogList($where, $pageNum);
    }

    /**
     * 虚拟货道排序操作日志详情
     * 返回：设备列表、单品数据、组合商品数据、排序好的数据列表
     */
    public function getSortLogDetail()
    {
        $log_id = input('log_id');
        if (!$log_id) return returnState(100, 'log_id不能为空');
        return $this->app->weicheng->getMcSortLogDetail($log_id);
    }

    /**
     * 批量修改货道信息
     * @return array|\think\response\Json
     */
    public function batchUpdate()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.updateAll');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $where = $this->getWhere(['m_id'=>$postData['m_id']], false, []);
        return $this->app->machineChannel->batchUpdateMc($postData, $where);
    }

    /**
     * 批量还原货道信息
     * @return array|\think\response\Json
     */
    public function batchRestore()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.updateAll');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $where = $this->getWhere(['m_id'=>$postData['m_id']], false, []);
        return $this->app->machineChannel->batchRestoreMc($postData, $where);
    }

    /**
     * 远程下架货道商品回收
     * @return array|string
     */
    public function remoteRemoval()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.remoteRemoval');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineChannel->remoteRemoval($postData);
    }
}