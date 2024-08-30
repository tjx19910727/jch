<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/27
 * Time: 8:45
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineFreeGoods;

class MachineFreeGoods extends Common
{

    protected $field = "*";
    protected $validatePath = VMachineFreeGoods::class . ".";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, []);
        if (!isset($where['mf_id']) || !$where['mf_id']) return returnState(100,lang("VMachineFree.mf_id_require"));
        $this->field = "mfg_id,mf_id,mfg.g_id,mfg.sale_amount,rise_fall_ratio,g_name,g_type,pic,gc_name,sku,retail_price";
        return $this->app->machineFreeGoods->getList($where,$pageNum,$this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineFreeGoods->getFind($where,$this->field);
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        $check = $this->app->machineFreeGoods->getMachineFreeGoodsFind(['mf_id' => $postData['mf_id'],'g_id' => $postData['g_id']]);
        if ($check) return returnState(100,lang("VMachineFree.g_id_unique"));
        return $this->app->machineFreeGoods->add($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineFreeGoods->update($postData,[],['sale_amount','rise_fall_ratio']);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineFreeGoods->del($postData);
    }
}