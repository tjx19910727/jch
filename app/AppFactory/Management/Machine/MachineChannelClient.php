<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:42
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Traits\Machine\MachineChannelTrait;
use app\AppFactory\Management\ManagementClient;

class MachineChannelClient extends ManagementClient
{
    use MachineChannelTrait;

    /**
     * 获取空槽、BAD、空货数量
     * @param $where
     * @return array
     */
    public function getData($where)
    {
        $whereEmpty = $where;
        $whereEmpty['g_id'] = 0;
        $empty = $this->getMachineChannelCount($whereEmpty);

        $whereBad = $where;
        $whereBad['status'] = 3;
        $bad = $this->getMachineChannelCount($whereBad);

        $whereStockOut = $where;
        $whereStockOut['stock'] = 0;
        $stockOut = $this->getMachineChannelCount($whereStockOut);

        $data = [
            "empty" => $empty,
            "bad" => $bad,
            "stockOut" => $stockOut,
        ];
        return $data;
    }

    /**
     * 获取空槽货道列表
     * @param $where
     * @return array|string
     */
    public function getEmptyList($where)
    {
        $where['g_id'] = 0;
        $list = $this->getMachineChannelList($where,0,'m_id,machine_id, 
        (SELECT machine_name FROM machine m WHERE m.m_id = a.m_id) machine_name ,
        count(mc_id) empty_num','','','m_id');
        if ($list) {
            foreach ($list as $key => $value) {
                $value['total_channel'] = $this->getMachineChannelCount(['machine_id' => $value['machine_id']]);
                $emptyList = $this->getMachineChannelColumn(['machine_id' => $value['machine_id'],'g_id' => 0],'channel_code');
                $value['empty_channel'] = implode(",",$emptyList ?? []);
                $value['empty_ratio'] =  bcmul(bcdiv($value['empty_num'],$value['total_channel'],3),100,1) . "%";
            }
        }
        return $this->rQ($list);
    }

    /**
     * 获取Bad货道列表
     * @param $where
     * @return array|string
     */
    public function getBadList($where)
    {
        $where['status'] = 3;
        $list = $this->getMachineChannelList($where,0,'m_id,machine_id, 
        (SELECT machine_name FROM machine m WHERE m.m_id = a.m_id) machine_name ,
        count(mc_id) bad_num','','','m_id');
        if ($list) {
            foreach ($list as $key => $value) {
                $value['total_channel'] = $this->getMachineChannelCount(['machine_id' => $value['machine_id']]);
                $badList = $this->getMachineChannelColumn(['machine_id' => $value['machine_id'],'status' => 3],'channel_code');
                $value['bad_channel'] = implode(",",$badList ?? []);
                $value['bad_ratio'] =  bcmul(bcdiv($value['bad_num'],$value['total_channel'],3),100,1) . "%";
            }
        }
        return $this->rQ($list);
    }

    /**
     * 获取空货列表
     * @param $where
     * @return array|string
     */
    public function getStockOutList($where)
    {
        $where['stock'] = 0;
        $list = $this->getMachineChannelList($where,0,'m_id,machine_id, 
        (SELECT machine_name FROM machine m WHERE m.m_id = a.m_id) machine_name ,
        count(mc_id) stock_out_num','','','m_id');
        if ($list) {
            foreach ($list as $key => $value) {
                $value['total_channel'] = $this->getMachineChannelCount(['machine_id' => $value['machine_id']]);
                $stockOutList = $this->getMachineChannelColumn(['machine_id' => $value['machine_id'],'stock' => 0],'channel_code');
                $value['stock_out_channel'] = implode(",",$stockOutList ?? []);
                $value['stock_out_ratio'] =  bcmul(bcdiv($value['stock_out_num'],$value['total_channel'],3),100,1) . "%";
            }
        }
        return $this->rQ($list);
    }

    /**
     * 修改货道信息
     * @param $postData
     * @return array|string
     */
    public function updateMc($postData)
    {
        $result = $this->updateMachineChannel($postData);
        if ($result) {
            $mc = $this->getMachineChannelFind(['mc_id' => $postData['mc_id']],'machine_id,mc_id');
            $this->sendMcToMachine($mc);
            return $this->r(200,$this->lang("action_success"));
        }
        return $this->r(100,$this->lang('action_fail'));
    }

    /**
     * 发送触发货道更新数据
     * @param $mc
     */
    public function sendMcToMachine($mc)
    {
        $config = [
            "machine_id" => $mc['machine_id'],
            "key" => env("api.md5Key"),
        ];
        $app = AppFactory::machine($config);
        $app->sendMq->triggerUpdateMc($mc['mc_id']);
    }
}