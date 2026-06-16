<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:35
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineGoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineModel;
use app\AppFactory\Kernel\Model\Mall\MallMachineModel;
use app\AppFactory\Kernel\Model\Mall\MallModel;
use app\AppFactory\Kernel\Model\Goods\GoodsModel;
use app\AppFactory\Kernel\Support\Validate\Machine\VChannel;
use app\AppFactory\Kernel\Traits\Mall\MallMachineTrait;

trait MachineChannelTrait
{
    use MallMachineTrait;

    /**
     * 增加指定字段数值
     * @param $where
     * @param $field
     * @param int $inc
     * @return mixed
     */
    public function setMachineChannelInc($where, $field, $inc = 1)
    {
        return MachineChannelModel::setInc($where, $field, $inc);
    }

    /**
     * 减少指定字段数值
     * @param $where
     * @param $field
     * @param int $dec
     * @return mixed
     */
    public function setMachineChannelDec($where, $field, $dec = 1)
    {
        return MachineChannelModel::setDec($where, $field, $dec);
    }

    public function getMachineChannelCount($where)
    {
        return MachineChannelModel::getCount($where);
    }

    public function getMachineChannelCountV2($where)
    {
        $whereRaw = "(a.channel_position <> 2 OR EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = a.m_id AND mi.sub_cabinet = 1))";
        if (isset($where['raw'])) {
            $whereRaw .= " AND " . $where['raw'];
            unset($where['raw']);
        }
        return MachineChannelModel::alias("a")->where($where)->whereRaw($whereRaw)->count();
    }

    public function getMachineChannelColumnV2($where, $column, $key = "")
    {
        $whereRaw = "EXISTS(SELECT 1 FROM machine_info mi WHERE mi.m_id = a.m_id AND mi.sub_cabinet = 1)";
        if (isset($where['raw'])) {
            $whereRaw .= " AND " . $where['raw'];
            unset($where['raw']);
        }
        return MachineChannelModel::alias("a")->where($where)->whereRaw($whereRaw)->column($column, $key);
    }

    public function getMachineChannelSum($where, $sum)
    {
        return MachineChannelModel::getSum($where, $sum);
    }

    public function getMachineChannelValue($where, $value)
    {
        return MachineChannelModel::getFieldValue($where, $value);
    }

    public function getMachineChannelColumn($where, $column, $key = "")
    {
        return MachineChannelModel::getColumn($where, $column, $key);
    }

    /**
     * 查询一条货道
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getMachineChannelFind($where, $field = "*", $order = "")
    {
        return MachineChannelModel::getFind($where, $field, $order);
    }

    public function getMachineChannelList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = '')
    {
        return MachineChannelModel::getList($where, $pageNum, $field, $order, $eachFun, $group);
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
    public function getMachineChannelJoinMfgList($where, $field = "*", $order = "")
    {
        return MachineChannelModel::joinMfgList($where, $field, $order);
    }

    public function getMachineChannelJoinGoodsList($where, $field = "*", $order = "", $group = "")
    {
        return MachineChannelModel::joinGoodsList($where, $field, $order, $group);
    }

    public function addMachineChannel($insert)
    {
        $data = MachineChannelModel::create($insert);
        return $data->mc_id;
    }

    public function addMachineMoreChannel($insert)
    {
        $mc = new MachineChannelModel();
        return $mc->saveAll($insert);
    }

    public function updateMachineChannel($update, $where = [], $field = [])
    {
        return MachineChannelModel::update($update, $where, $field);
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
                    $whereMc = [];
                    try {
                        validate(VChannel::class)->scene("subChannel")->check($value);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rValidate($this->lang($e->getMessage()));
                    }
                    $value['m_id'] = $this->machine['m_id'];
                    $value['machine_id'] = $this->machine['machine_id'];
                    if (isset($value['mc_id']) && $value['mc_id']) {
                        $whereMc['mc_id'] = $value['mc_id'];
                    } else {
                        $whereMc['m_id'] = $this->machine['m_id'];
                        $whereMc['channel_code'] = $value['channel_code'];
                        $whereMc['channel_position'] = $value['channel_position'];
                    }

                    $mc = $this->getMachineChannelFind($whereMc);
                    actionLog($this->getLS(), '查询货道');
                    if (!$mc) {
                        $mc = $value;
                        if (isset($value['g_id'])) {
                            $gField = "g_id,g_name,gc_id,gc_name,bar_code,sku,pic,cost_price,market_price,retail_price,ao_id";
                            $g = $this->getGoodsFind(['g_id' => $value['g_id']], $gField);
                            if ($g) {
                                $g = obj2arr($g);
                                $g['pic'] = str_replace($this->host, '', $g['pic']);
                                $g['mg_id'] = ($this->getMachineGoodsValue(['g_id' => $g['g_id'], 'm_id' => $this->machine['m_id']], 'mg_id') ?? 0);
                                $mc = array_merge($mc, $g);
                            }
                        }
                        $mc['mc_id'] = $this->addMachineChannel($mc);
                        if (!$mc['mc_id']) {
                            $this->rollbackTrans();
                            return $this->rFail($this->lang("VChannel.add_channel_fail") . ":" . $mc['channel_code']);
                        }
                        // 20250604 新增货道，增加“上货”商品变化记录
                        $insertGChange = [
                            "m_id" => $this->machine['m_id'],
                            "machine_id" => $this->machine['machine_id'],
                            "machine_name" => $this->machine['machine_name'],
                            "mc_id" => $mc['mc_id'],
                            "channel_code" => $mc['channel_code'],
                            "mg_id" => $mc['mg_id'] ?? 0,
                            "g_id" => $mc['g_id'] ?? 0,
                            "g_name" => $mc['g_name'] ?? "",
                            "gc_id" => $mc['gc_id'] ?? 0,
                            "gc_name" => $mc['gc_name'] ?? "",
                            "pic" => $mc['pic'] ?? "",
                            "sku" => $mc['sku'] ?? "",
                            "bar_code" => $mc['bar_code'] ?? "",
                            "ao_id" => $this->machine['ao_id'],
                            "change_value" => $value['stock'] ?? 0,
                            "type" => 2 ,   // 2：创建上货
//                            "desc" => $this->lang("goodsChange.terminal_create_inc_stock"),
                            "position" => 1,
                        ];
                        $this->addGoodsChange($insertGChange);
                    } else {
                        $mc = $mc->toArray() ?? obj2arr($mc);

                        $insertGChange = [
                            "m_id" => $this->machine['m_id'],
                            "machine_id" => $this->machine['machine_id'],
                            "machine_name" => $this->machine['machine_name'],
                            "mc_id" => $mc['mc_id'],
                            "channel_code" => $mc['channel_code'],
                            "mg_id" => $mc['mg_id'] ?? 0,
                            "g_id" => $mc['g_id'] ?? 0,
                            "g_name" => $mc['g_name'] ?? "",
                            "gc_id" => $mc['gc_id'] ?? 0,
                            "gc_name" => $mc['gc_name'] ?? "",
                            "pic" => $mc['pic'] ?? "",
                            "sku" => $mc['sku'] ?? "",
                            "bar_code" => $mc['bar_code'] ?? "",
                            "ao_id" => $this->machine['ao_id'],
                            "change_value" => $value['stock'] ?? 0,
                            "position" => 1,
                        ];
                        // 20250604 检查货道库存上货或下货，变化数量 = 上报stock - 当前货道stock
                        if (isset($value['stock']) && $value['stock'] != $mc['stock']) {
                            $changeValue = bcsub($value['stock'],$mc['stock']);
                            $insertGc = array_merge($insertGChange, [
                                "change_value" => $changeValue,
                                "type" => $changeValue > 0 ? 2 : 3 ,    // >0：上货，<0 下货
                                "desc" => $value['status'] == 3 ? $this->lang("goodsChange.terminal_mc_bad") : $this->lang("goodsChange.terminal_mc_not_bad"),
                            ]);
                            $this->addGoodsChange($insertGc);
                        }
                        // 20250604 检查Bad状态，终端BAD 10 或终端恢复BAD 11
                        if (isset($value['status']) && $value['status'] != $mc['status'] && in_array($value['status'], [1, 3])) {
                            $insertGc = array_merge($insertGChange, [
                                "change_value" => $mc['stock'],
                                "type" => $value['status'] == 3 ? 10 : 11 ,   // 3：终端BAD，1：终端恢复BAD
                                "desc" => $value['status'] == 3 ? $this->lang("goodsChange.terminal_mc_bad") : $this->lang("goodsChange.terminal_mc_not_bad"),
                            ]);
                            $this->addGoodsChange($insertGc);
                        }

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
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 货道槽位照片上传
     * @return MachineChannelTrait
     */
    public function channelImg()
    {
        $result = $this->updateMachineChannel(['channel_img' => $this->message['path']], ['m_id' => $this->machine['m_id'], 'channel_code' => $this->message['channel_code']]);
        actionLog($this->getLS(), '【SQL】保存货道槽位照片', 'DataUpload');
        return $result;
    }


    public function getRateOrGiftPoints($mc)
    {
        // Safe handling: guard against null/invalid $mc. Return explicit defaults when missing.
        if (empty($mc)) {
            return ['intergral_rate' => 0, 'gift_points' => 0];
        }

        // Normalize $mc to array for safe access if it's an object
        $mcArr = $mc;
        if (is_object($mc)) {
            // obj2arr helper exists elsewhere in codebase
            $mcArr = function_exists('obj2arr') ? obj2arr($mc) : (array)$mc;
        }

        // If device-channel level config exists, use it
        $ir = $mcArr['intergral_rate'] ?? null;
        $gp = $mcArr['gift_points'] ?? null;
        if ($ir !== null || $gp !== null) {
            return [
                'intergral_rate' => $ir ?: 0,
                'gift_points' => $gp ?: 0,
            ];
        }

        // Fallback to machine-goods level (requires g_id and machine_id)
        $g_id = $mcArr['g_id'] ?? null;
        $machine_id = $mcArr['machine_id'] ?? null;
        if ($g_id && $machine_id) {
            $machineGoodsInfo = MachineGoodsModel::getFind(['g_id' => $g_id, 'machine_id' => $machine_id], 'intergral_rate,gift_points');
            if ($machineGoodsInfo) {
                return [
                    'intergral_rate' => $machineGoodsInfo['intergral_rate'] ?? 0,
                    'gift_points' => $machineGoodsInfo['gift_points'] ?? 0,
                ];
            }

            $goodsInfo = GoodsModel::getFind(['g_id' => $g_id], 'intergral_rate,gift_points');
            if ($goodsInfo) {
                return [
                    'intergral_rate' => $goodsInfo['intergral_rate'] ?? 0,
                    'gift_points' => $goodsInfo['gift_points'] ?? 0,
                ];
            }

            $machineInfo = MachineModel::getFind(['machine_id' => $machine_id]);
            if ($machineInfo) {
                $mallMachine = $this->getMallMachineFind(['machine_id' => $machine_id]);
                if ($mallMachine && !empty($mallMachine['m_id'])) {
                    $mall = MallModel::getFind(['mall_id' => $mallMachine['m_id']], 'intergral_rate');
                    if ($mall && !empty($mall['intergral_rate'])) {
                        return ['intergral_rate' => $mall['intergral_rate'], 'gift_points' => 0];
                    }
                }
            }
        }

        return ['intergral_rate' => 0, 'gift_points' => 0];
    }
}
