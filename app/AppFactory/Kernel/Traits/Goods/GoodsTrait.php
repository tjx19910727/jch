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

trait GoodsTrait
{
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
        return GoodsModel::insertAll($data);
    }

    public function addGoods($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        !isset($this->manager['ao_id']) ? :$insert['ao_id'] = $this->manager['ao_id'];
        $data = GoodsModel::create($insert);
        return $data->g_id;
    }

    public function updateGoods($update,$where = [],$field = [],$updateType = 1)
    {
        !isset($this->manager['manager_id']) ? : $update['update_id'] = $this->manager['manager_id'];
        $result = GoodsModel::update($update,$where,$field);
        if ($result && $updateType) {
            // ThinkPHP 的 update 返回对象只包含实际更新字段时可能没有主键，必须从更新条件回收 g_id。
            $gId = $this->resolveUpdatedGoodsId($result, $update, $where);
            if ($gId <= 0) {
                throw new \RuntimeException('更新商品后无法确定商品ID');
            }
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
        $gIds = GoodsModel::where($where)->column('g_id');
        $result = GoodsModel::destroy($where);
        if ($result && $gIds) Db::name('goods_currency_price')->whereIn('g_id', $gIds)->delete();
        return $result;
    }
}
