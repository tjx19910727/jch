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
use think\facade\Db;
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
        if (!$result) {
            return $result;
        }

        // 更新字段可能不包含 g_id（例如多币种商品编辑），必须从更新条件中回收商品 ID。
        $gId = $this->resolveUpdatedGoodsId($result, $update, $where);
        if ($gId <= 0) {
            throw new \RuntimeException('更新商品后无法确定商品ID');
        }

        // 商品资料/价格/上下架等变化实时上报（内部会自动联动装载该商品的 ao_id=17 设备）。
        $this->reportGoodsChangedToThirdParty($gId);

        if ($updateType) {
            $newGoods = GoodsModel::getFind(['g_id' => $gId],'g_id,g_name,gc_id,gc_name,pic,sku,bar_code');
            if (!$newGoods) {
                throw new \RuntimeException('更新后的商品不存在');
            }
            $new = $newGoods->toArray();
            MachineGoodsModel::update($new,['g_id' => $gId]);
            MachineChannelModel::update($new,['g_id' => $gId]);
            ActivityGoodsModel::update([
                'g_name' => $new['g_name'],
                'pic' => $new['pic'],
                'sku' => $new['sku'],
            ],['g_id' => $gId]);
        }
        return $result;
    }

    /**
     * 优先使用模型返回主键，并兼容仅通过 where 指定 g_id 的更新调用。
     */
    protected function resolveUpdatedGoodsId($result, array $update, array $where)
    {
        if ($result && isset($result['g_id']) && intval($result['g_id']) > 0) {
            return intval($result['g_id']);
        }
        if (isset($update['g_id']) && intval($update['g_id']) > 0) {
            return intval($update['g_id']);
        }
        if (isset($where['g_id']) && !is_array($where['g_id']) && intval($where['g_id']) > 0) {
            return intval($where['g_id']);
        }
        foreach ($where as $condition) {
            if (is_array($condition)
                && isset($condition[0], $condition[1], $condition[2])
                && $condition[0] === 'g_id'
                && in_array(strtolower((string)$condition[1]), ['=', 'eq'], true)
                && intval($condition[2]) > 0
            ) {
                return intval($condition[2]);
            }
        }
        return 0;
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
