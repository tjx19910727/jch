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
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineChannel->getList($where,$pageNum,$this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineChannel->getFind($where,$this->field);
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
        return $this->app->machineChannel->exportMcSku($m_id);
    }

    /**
     * 导出设备货架
     * @return array|\think\response\Json
     */
    public function export()
    {
        $m_id = input("m_id");
        return $this->app->machineChannel->exportMc($m_id);
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

    //设置微程商品在虚拟货道排序
    public function setWcGoods2Mc(){
        $m_id = input("m_id");
        $wc_goods_local_ids = input('wc_goods_local_ids');
        $wc_goods_local_ids_arr = explode(',', $wc_goods_local_ids);
        return $this->app->weicheng->setWcMachineChannelLists($m_id, $wc_goods_local_ids_arr);
    }
}