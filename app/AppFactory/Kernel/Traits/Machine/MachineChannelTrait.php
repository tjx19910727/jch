<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:35
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Support\Validate\Machine\VChannel;

trait MachineChannelTrait
{

    /**
     * 增加指定字段数值
     * @param $where
     * @param $field
     * @param int $inc
     * @return mixed
     */
    public function setMachineChannelInc($where,$field,$inc = 1)
    {
        return MachineChannelModel::setInc($where,$field,$inc);
    }

    /**
     * 减少指定字段数值
     * @param $where
     * @param $field
     * @param int $dec
     * @return mixed
     */
    public function setMachineChannelDec($where,$field,$dec = 1)
    {
        return MachineChannelModel::setDec($where,$field,$dec);
    }

    public function getMachineChannelCount($where)
    {
        return MachineChannelModel::getCount($where);
    }

    public function getMachineChannelSum($where,$sum)
    {
        return MachineChannelModel::getSum($where,$sum);
    }
    public function getMachineChannelValue($where,$value)
    {
        return MachineChannelModel::getFieldValue($where,$value);
    }

    public function getMachineChannelColumn($where,$column,$key = "")
    {
        return MachineChannelModel::getColumn($where,$column,$key);
    }

    public function getMachineChannelFind($where,$field = "*",$order = "")
    {
        return MachineChannelModel::getFind($where,$field,$order);
    }

    public function getMachineChannelList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "",$group = '')
    {
        return MachineChannelModel::getList($where,$pageNum,$field,$order,$eachFun,$group);
    }

    /**
     * 获取货道关联的自由组合商品表
     * @param $where
     * @param string $field
     * @param string $order
     * @return MachineChannelModel[]|array|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMachineChannelJoinMfgList($where,$field = "*", $order = "")
    {
        return MachineChannelModel::joinMfgList($where,$field,$order);
    }

    public function getMachineChannelJoinGoodsList($where,$field = "*",$order = "",$group = "")
    {
        return MachineChannelModel::joinGoodsList($where,$field,$order,$group);
    }

    public function addMachineChannel($insert)
    {
        $data = MachineChannelModel::create($insert);
        return $data->mc_id;
    }

    public function updateMachineChannel($update,$where = [],$field = [])
    {
        return MachineChannelModel::update($update,$where,$field);
    }

    public function delMachineChannel($where)
    {
        $result = MachineChannelModel::whereDel($where);
        return $result;
    }

    /**
     * 终端上报设备货道
     * http://sd.dakemakeji.com/web/#/78?page_id=2209
     * @return array
     */
    public function terminalSubChannel()
    {
        $channelList = [];
        $flag = [];
        $this->startTrans();
        try {
            if (isset($this->data['delList']) && $this->data['delList']) {
                $flag[] = $this->delMachineChannel([['mc_id', 'in', $this->data['delList']]]);
            }
            if (isset($this->data['mcList'])) {
                $mcList = json2arr($this->data['mcList']);
                foreach ($mcList as $key => $value) {
                    try {
                        validate(VChannel::class)->scene("subChannel")->check($value);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rValidate($this->lang($e->getMessage()));
                    }
                    $value['m_id'] = $this->machine['m_id'];
                    $value['machine_id'] = $this->machine['machine_id'];
                    $mc = $this->getMachineChannelFind(['channel_code' => $value['channel_code'], 'm_id' => $this->machine['m_id'], 'channel_position' => $value['channel_position']]);
                    if (!$mc) {
                        $mc = $value;
                        if (isset($value['g_id'])) {
                            $gField = "g_id,g_name,gc_id,gc_name,bar_code,sku,pic,cost_price,market_price,retail_price";
                            $g = $this->getGoodsFind(['g_id' => $value['g_id']], $gField);
                            if ($g) {
                                $g = obj2arr($g);
                                $g['pic'] = str_replace($this->host,'',$g['pic']);
                                $g['mg_id'] = ($this->getMachineGoodsValue(['g_id' => $g['g_id'], 'm_id' => $this->machine['m_id']], 'mg_id') ?? 0);
                                $mc = array_merge($mc, $g);
                            }
                        }
                        $mc['mc_id'] = $this->addMachineChannel($mc);
                        if (!$mc['mc_id']) {
                            $this->rollbackTrans();
                            return $this->rFail($this->lang("VChannel.add_channel_fail") . ":" . $mc['channel_code']);
                        }
                    } else {
                        $mc = obj2arr($mc);
                        $mc = array_merge($mc, $value);
                        $mc = $this->updateMachineChannel($mc);
                        if (!$mc) {
                            $this->rollbackTrans();
                            return $this->rFail($this->lang("VChannel.update_channel_fail") . ":" . $mc['channel_code']);
                        }
                    }
                    $channelList[] = $mc;
                }
            }
            return $this->checkTrans($this->checkFlag($flag));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 货道槽位照片上传
     * @return MachineChannelTrait
     */
    public function channelImg()
    {
        $result = $this->updateMachineChannel(['channel_img' => $this->message['path']],['m_id' => $this->machine['m_id'],'channel_code' => $this->message['channel_code']]);
        actionLog($this->getLS(),'【SQL】保存货道槽位照片','DataUpload');
        return $result;
    }
}