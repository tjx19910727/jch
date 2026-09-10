<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 10:58
 */

namespace app\AppFactory\Kernel\Traits\Goods;


use app\AppFactory\Kernel\Model\Activity\ActivityGoodsModel;
use app\AppFactory\Kernel\Model\Goods\GoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineGoodsModel;
use app\AppFactory\Kernel\Traits\ThirdParty\ThirdPartySyncReportTrait;

trait GoodsTrait
{
    use ThirdPartySyncReportTrait;

    public function getGoodsValue($where,$value)
    {
        return GoodsModel::getFieldValue($where,$value);
    }

    public function getGoodsColumn($where,$column)
    {
        return GoodsModel::getColumn($where,$column);
    }

    /**
     * 获取商品信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getGoodsFind($where,$field = "*",$order = "")
    {
        return GoodsModel::getFind($where,$field,$order);
    }

    public function getGoodsList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "",$group = "",$limit = "")
    {
        return GoodsModel::getList($where,$pageNum,$field,$order,$eachFun,$group,$limit);
    }

    public function getGoodsJoinMachineGoodsList($where,$pageNum = 0,$field = "*", $order = "",$m_id = 0)
    {
        return GoodsModel::joinMachineGoodsList($where,$pageNum,$field,$order,$m_id);
    }

    public function getGoodsJoinMachineGoodsFind($where,$field,$order)
    {
        return GoodsModel::joinMachineGoodsFind($where,$field,$order);
    }

    public function addMoreGoods($data)
    {
        // 批量新增无法回读自增 g_id，不做 A 层实时上报；
        // 新增行 update_time 会落在扫描窗口内，由 third_party_sync scan（C 层）兜底。
        return GoodsModel::insertAll($data);
    }

    public function addGoods($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        !isset($this->manager['ao_id']) ? :$insert['ao_id'] = $this->manager['ao_id'];
        $data = GoodsModel::create($insert);
        $this->reportGoodsChangedToThirdParty($data->g_id);
        return $data->g_id;
    }

    public function updateGoods($update,$where = [],$field = [],$updateType = 1)
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        $result = GoodsModel::update($update,$where,$field);
        if ($result) {
            $gId = intval($result['g_id'] ?? 0);
            if ($gId <= 0) {
                $gId = intval($update['g_id'] ?? 0);
            }
            if ($gId > 0) {
                // 商品资料/价格/上下架等变化实时上报（内部会自动联动装载该商品的 ao_id=17 设备）。
                $this->reportGoodsChangedToThirdParty($gId);
            }
            if ($updateType) {
                $new = GoodsModel::getFind(['g_id' => $result['g_id']],'g_id,g_name,gc_id,gc_name,pic,sku,bar_code')->toArray();
                MachineGoodsModel::update($new,['g_id' => $result['g_id']]);
                MachineChannelModel::update($new,['g_id' => $result['g_id']]);
                ActivityGoodsModel::update([
                    'g_name' => $new['g_name'],
                    'pic' => $new['pic'],
                    'sku' => $new['sku'],
                ],['g_id' => $result['g_id']]);
            }
        }
        return $result;
    }

    public function delGoods($where)
    {
        // 物理删除前先记录属于核心主体的商品，删除后按 delete 语义上报。
        $removed = [];
        try {
            $rows = GoodsModel::getList($where, 0, 'g_id,ao_id');
            foreach ($rows as $row) {
                $row = is_object($row) && method_exists($row, 'toArray') ? $row->toArray() : (array)$row;
                if (intval($row['ao_id'] ?? 0) === (int)$this->thirdPartySyncCoreAoId) {
                    $removed[intval($row['g_id'])] = intval($row['ao_id']);
                }
            }
        } catch (\Throwable $e) {
            $removed = [];
        }
        $result = GoodsModel::destroy($where);
        foreach ($removed as $gId => $aoId) {
            $this->reportGoodsChangedToThirdParty($gId, 'delete', $aoId);
        }
        return $result;
    }
}