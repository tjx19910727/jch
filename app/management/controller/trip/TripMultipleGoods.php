<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/10
 * Time: 10:17
 */

namespace app\management\controller\trip;


use app\management\controller\Common;
use app\management\validate\Trip\VTripMultipleGoods;

class TripMultipleGoods extends Common
{

    protected $field = "*";
    protected $validatePath = VTripMultipleGoods::class . ".";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        $field = "tmg_id,tm_id,tmg.g_id,tmg.is_required,tmg.buy_lower,tmg.buy_upper,tmg.sale_amount,tmg.rise_fall_ratio,
        g.g_name,g.gc_name,g.pic,g.sku,g.model,g.performance,g.g_type,g.retail_price";
        return returnState(200,lang("query_success"),$this->app->tripMultipleGoods->getTripMultipleGoodsJoinGoodsList($where,$pageNum,$field,"tmg_id desc"));
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->tripMultipleGoods->getFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $check = $this->app->tripMultipleGoods->getTripMultipleGoodsFind(['tm_id' => $postData['tm_id'],'g_id' => $postData['g_id']]);
        if ($check) return returnState(100,lang("VTripMultiple.g_id_unique"));
        return $this->app->tripMultipleGoods->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->tripMultipleGoods->update($postData,[],['sale_amount','rise_fall_ratio','buy_lower','buy_upper']);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->tripMultipleGoods->del($postData);
    }
}