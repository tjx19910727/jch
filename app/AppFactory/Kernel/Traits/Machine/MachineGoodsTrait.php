<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:38
 */

namespace app\AppFactory\Kernel\Traits\Machine;


use app\AppFactory\Kernel\Model\Machine\MachineGoodsModel;
use app\AppFactory\Kernel\Support\Validate\Machine\VMachineGoods;
use app\AppFactory\Kernel\Service\Currency\MachineCurrencyPriceService;
use think\facade\Db;

trait MachineGoodsTrait
{
    public function setMachineGoodsInc($where,$field ,$inc = 1)
    {
        return MachineGoodsModel::setInc($where,$field,$inc);
    }

    public function setMachineGoodsDec($where,$field,$dec = 1)
    {
        return MachineGoodsModel::setDec($where,$field,$dec);
    }

    public function getMachineGoodsColumn($where,$column)
    {
        return MachineGoodsModel::getColumn($where,$column);
    }

    public function getMachineGoodsValue($where,$value)
    {
        return MachineGoodsModel::getFieldValue($where,$value);
    }

    public function getMachineGoodsFind($where,$field = "*",$order = "")
    {
        return MachineGoodsModel::getFind($where,$field,$order);
    }

    public function getMachineGoodsList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "",$group = '')
    {
        return MachineGoodsModel::getList($where,$pageNum,$field,$order,$eachFun,$group);
    }

    public function addMachineGoods($insert)
    {
        $data = MachineGoodsModel::create($insert);
        return $data->mg_id;
    }

    public function updateMachineGoods($update,$where = [],$field = [])
    {
        return MachineGoodsModel::update($update,$where,$field);
    }

    public function afterMgInsert($mg)
    {
        return MachineGoodsModel::AfterInsert($mg);
    }

    public function afterMgUpdate($mg_id)
    {
        return MachineGoodsModel::AfterUpdate($mg_id);
    }

    public function delMachineGoods($where)
    {
        $mgIds = MachineGoodsModel::where($where)->column('mg_id');
        $result = MachineGoodsModel::whereDel($where);
        if ($result && $mgIds) Db::name('machine_goods_currency_price')->whereIn('mg_id', $mgIds)->delete();
        return $result;
    }

    public function getMachineGoodsListJoinGoods($where,$pageNum = 0,$field = "*",$order = "")
    {
        return MachineGoodsModel::getMGoodsListJoinGoods($where,$pageNum,$field,$order);
    }

    // mg_id g_id  available_stock  disabled_stock  reserve_stock  standby_stock  pre_loading_stock is_shelf

    /**
     * 设备终端上报设备备用商品信息
     * @return mixed
     */
    public function terminalSubMachineGoods()
    {
        $this->data['mgList'] = json2arr($this->data['mgList']);
        $mgList = [];
        $this->startTrans();
        try {
            foreach ($this->data['mgList'] as $key => $value) {
                try {
                    validate(VMachineGoods::class)->scene("subMachineGoods")->check($value);
                } catch (\Exception $e) {
                    $this->rollbackTrans();
                    return $this->rValidate($this->lang($e->getMessage()));
                }
                if ($value['mg_id']) {
                    $mg_id = $value['mg_id'];
                    $allowed = ['available_stock', 'disabled_stock', 'reserve_stock', 'standby_stock', 'pre_loading_stock', 'is_shelf'];
                    $safeUpdate = array_intersect_key($value, array_flip($allowed));
                    $result = $safeUpdate ? $this->updateMachineGoods($safeUpdate, ['mg_id' => $mg_id, 'm_id' => $this->machine['m_id']]) : true;
                    if (!$result) {
                        $this->rollbackTrans();
                        return $this->rFail($this->lang("VMachineGoods.update_machine_goods_fail"));
                    }
                } else {
                    if (isset($value['mg_id'])) unset($value['mg_id']);
                    $g = $this->getGoodsFind(['g_id' => $value['g_id']], 'g_id,g_name,gc_id,gc_name,bar_code,sku,pic,ao_id');
                    if ($g) {
                        $g = obj2arr($g);
                        $config = (new MachineCurrencyPriceService())->getMachineCurrency($this->machine['m_id']);
                        $price = Db::name('goods_currency_price')->where(['g_id' => intval($g['g_id']), 'currency_code' => $config['currency_code']])->find();
                        if (!$price) throw new \InvalidArgumentException('核心商品缺少当前设备币种价格');
                        $g = array_merge($g, [
                            'cost_price' => $price['cost_price'],
                            'market_price' => $price['market_price'],
                            'retail_price' => $price['retail_price'],
                        ]);
                        $g['pic'] = str_replace($this->host,'',$g['pic']);
                        $checkMg = $this->getMachineGoodsFind(['m_id' => $this->machine['m_id'], 'g_id' => $g['g_id']]);
                        if (!$checkMg) {
//                            $this->rollbackTrans();
//                            return $this->rFail($this->lang("VMachineGoods.machine_goods_exits"));
//                        }
                            $mg = [
                                "m_id" => $this->machine['m_id'],
                                "machine_id" => $this->machine['machine_id'],
                            ];
                            $mg = array_merge($mg, $value, $g);
                            $mg_id = $this->addMachineGoods($mg);
                            if (!$mg_id) throw new \RuntimeException('新增设备商品失败');
                            Db::name('machine_goods_currency_price')->insert([
                                'mg_id' => intval($mg_id),
                                'm_id' => intval($this->machine['m_id']),
                                'g_id' => intval($g['g_id']),
                                'currency_code' => $config['currency_code'],
                                'cost_price' => $price['cost_price'],
                                'market_price' => $price['market_price'],
                                'retail_price' => $price['retail_price'],
                                'creator' => 0,
                                'update_id' => 0,
                            ]);
                        } else {
                            $mgList[] = $checkMg;
                            continue;
                        }
                    } else {
                        $this->rollbackTrans();
                        return $this->rFail($this->lang("VMachineGoods.goods_no_data"));
                    }
                }
                $mg = $this->getMachineGoodsFind(['mg_id' => $mg_id]);
                $mgList[] = $mg;
            }
            $this->commitTrans();
            return $this->rAction($mgList);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }
}
