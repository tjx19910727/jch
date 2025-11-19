<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/19
 * Time: 9:10
 */

namespace app\AppFactory\Kernel\Model\Goods;


use app\AppFactory\Kernel\Model\BaseModel;

class GoodsMultipleModel extends BaseModel
{
    protected $pk = "gm_id";
    protected $name = "goods_multiple";

    /**
     * 获取关联设备列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param int $page
     * @return GoodsMultipleModel|array|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public static function joinGmm($where,$pageNum = 0,$field = "*",$order = "",$page = 1)
    {
        $data = self::alias("gm")
            ->join("goods_multiple_machine gmm","gmm.gm_id = gm.gm_id","left")
            ->where($where)
            ->field($field)
            ->order($order);
        if ($pageNum) {
            $data = $data->paginate(['list_rows' => $pageNum,'page' => $page,"query" => request()->param()], false)
                ->each(function ($item) {
                $item['gList'] = GoodsMultipleGoodsModel::getJoinGoodsList(['gm_id' => $item['gm_id']],
                    'gmg_id,gmg.g_id,selling_price,rise_fall_ratio,gmg.stock,g_name,g.pic,g.sku,g.sku2,g.bar_code,g.cost_price,g.g_type,g.performance');
                return $item;
            });
            return $data;
        }
        $data = $data->select()->toArray();
        if ($data) {
            foreach ($data as $key => $value) {
                $value['gList'] = GoodsMultipleGoodsModel::getJoinGoodsList(['gm_id' => $value['gm_id']],
                    'gmg_id,gmg.g_id,selling_price,rise_fall_ratio,g_name,g.pic,g.sku,g.sku2,g.bar_code,g.cost_price,g.g_type,g.performance');
                $data[$key] = $value;
            }
        }
        return $data;
    }

    /**
     * 获取关联商品列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return GoodsMultipleModel|array|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public static function joinGmg($where,$pageNum = 0,$field = "*",$order = "")
    {
        $data = self::alias("gm")
            ->join("goods_multiple_goods gmg","gmg.gm_id = gm.gm_id","left")
            ->join("goods g","g.g_id = gmg.g_id","left")
            ->where($where)
            ->field($field)
            ->order($order);
        if ($pageNum) {
            $data = $data->paginate($pageNum, false, ["query" => request()->param()])->each(function ($item) {
                $item['mList'] = GoodsMultipleMachineModel::getList(['gm_id' => $item['gm_id']],0,'gmm_id,m_id,machine_id,machine_name');
                return $item;
            });
            return $data;
        }
        $data = $data->select()->toArray();
        if ($data) {
            foreach ($data as $key => $value) {
                $value['mList'] = GoodsMultipleMachineModel::getList(['gm_id' => $value['gm_id']],0,'gmm_id,m_id,machine_id,machine_name');
                $data[$key] = $value;
            }
        }
        return $data;
    }
}