<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/24
 * Time: 0:00
 */

namespace app\AppFactory\Management\Goods;

use app\AppFactory\Kernel\Traits\Goods\GoodsBehaviorTrackingTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class GoodsBehaviorTrackingClient extends ManagementClient
{
    use GoodsBehaviorTrackingTrait;

    /**
     * 商品行为埋点列表（连表goods）
     * @param array $where 主表(gbt)条件
     * @param int $pageNum 分页条数
     * @param string $field 查询字段
     * @param string $order 排序
     * @param string $goodsName 商品名称模糊搜索
     */
    public function getTrackingList($where, $pageNum = 0, $field = "*", $order = "", $goodsName = '')
    {
        $mIds = $this->getAuthManagerMachineColumn(['manager_id' => $this->manager['manager_id']], "m_id");
        if ($mIds) $where[] = ['gbt.m_id', 'in', $mIds];

        $query = Db::name('goods_behavior_tracking')->alias('gbt')
            ->leftJoin('goods g', 'gbt.is_online = 2 AND g.g_id = gbt.goods_id')
            ->leftJoin('wc_goods_local wg', 'gbt.is_online = 1 AND wg.id = gbt.goods_id')
            ->where($where);

        if ($goodsName !== '') {
            $query->where(function ($query) use ($goodsName) {
                $query->where('g.g_name', 'like', '%' . $goodsName . '%')
                    ->whereOr('wg.g_name', 'like', '%' . $goodsName . '%');
            });
        }

        if ($pageNum) {
            return $this->rQ($query->field($field)->order($order)->paginate($pageNum, false, ["query" => request()->param()]));
        }

        return $this->rQ($query->field($field)->order($order)->select());
    }
}
