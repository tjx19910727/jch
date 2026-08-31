<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 16:12
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineGoods;

class MachineGoods extends Common
{

    protected $field = "mg_id,m_id,g_id,g_name,gc_id,gc_name,gc_sort,pic,sku,cost_price,market_price,retail_price,intergral_rate,gift_points,
    (SELECT sum(mc.stock) FROM machine_channel mc where mc.m_id = a.m_id AND mc.status = 1 AND mc.mg_id = a.mg_id) available_stock,
    (SELECT sum(mc.stock) FROM machine_channel mc where mc.m_id = a.m_id AND mc.status > 1 AND mc.mg_id = a.mg_id) disabled_stock,
    (SELECT sum(mc.frozen_stock) FROM machine_channel mc where mc.m_id = a.m_id AND mc.mg_id = a.mg_id) reserve_stock,auto_refund,
    standby_stock,machine_id,is_shelf,
    (SELECT GROUP_CONCAT(mgps.sp_id ORDER BY mgps.sort ASC,mgps.id ASC) FROM machine_goods_payee_strategy mgps WHERE mgps.mg_id = a.mg_id AND mgps.status = 1) sp_ids,
    (SELECT GROUP_CONCAT(sp.sp_name ORDER BY mgps.sort ASC,mgps.id ASC SEPARATOR '、') FROM machine_goods_payee_strategy mgps INNER JOIN strategy_payee sp ON sp.sp_id = mgps.sp_id WHERE mgps.mg_id = a.mg_id AND mgps.status = 1) sp_names,
    (SELECT GROUP_CONCAT(sp.payee_type ORDER BY mgps.sort ASC,mgps.id ASC) FROM machine_goods_payee_strategy mgps INNER JOIN strategy_payee sp ON sp.sp_id = mgps.sp_id WHERE mgps.mg_id = a.mg_id AND mgps.status = 1) payee_types,
    (SELECT MIN(sp.status) FROM machine_goods_payee_strategy mgps INNER JOIN strategy_payee sp ON sp.sp_id = mgps.sp_id WHERE mgps.mg_id = a.mg_id AND mgps.status = 1) strategies_status,
    (select machine_name FROM machine m WHERE m.m_id = a.m_id) machine_name";
    protected $validatePath = VMachineGoods::class;

    public function getList()
    {
        $postData = input();
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        $field = $this->getFieldWithCostPriceAuth($this->field, $hasCostPriceAuth);
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["g_name" => "like",'sku' => "like"]);
        return $this->app->machineGoods->getMgList($where, $pageNum, $field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineGoods->getMgFind($where, $this->field);
    }

    /**
     * 获取设备商品品类排序树形数据
     * @return array|\think\response\Json
     */
    public function getGcSort()
    {
        $where['m_id'] = input("m_id");
        if (!$where['m_id']) return returnState(100,lang("query_fail"));
        return $this->app->machineGoods->getGcList($where);
    }
    /**
     * 获取设备商品品类排序列表
     * @return array|\think\response\Json
     */
//    public function getGcSort()
//    {
//        $where['m_id'] = input("m_id");
//        return returnState(200,lang("query_success"),$this->app->machineGoods->getMachineGoodsList($where, 0, "gc_name,gc_id,`gc_sort`", "gc_sort asc", '', 'gc_id'));
//    }

    /**
     * 修改设备商品品类排序
     * @return array|\think\response\Json
     */
    public function updateGcSort()
    {
        $gcList = input('gcList');
        $gcList = json2arr($gcList);
        if ($gcList) {
            $this->app->machineGoods->startTrans();
            try {
                foreach ($gcList as $k => $v) {
                    $flag[] = $this->app->machineGoods->updateMachineGoods(['gc_sort' => $v['gc_sort']], ['gc_id' => $v['gc_id']]);
                }
                $result = flag_check($flag);
                return returnData($this->app->machineGoods->checkTrans($result));
            } catch (\Exception $e) {
                $this->app->machineGoods->rollbackTrans();
                actionException($e,1);
                return $this->app->machineGoods->rValidate($e->getMessage());
            }
        }
        return returnState(100,lang("VMachineGoods.update_require"));
    }

    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineGoods->addMg($postData);
    }

    public function update()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.update');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineGoods->updateMg($postData);
    }

    /**
     * 查询设备商品所属组织当前可用的收款策略。
     */
    public function getOrganizationPayeeStrategies()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.getOrganizationPayeeStrategies');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineGoods->getOrganizationPayeeStrategies($postData);
    }

    public function updateMoreByWhere()
    {
        $postData = input();
        $postData = json2arr($postData);
        if (!isset($postData['where']) || !$postData['where']) return returnState(100, lang("where_require"));
        if (!isset($postData['update']) || !$postData['update']) return returnState(100, lang("update_require"));
        return $this->app->machineGoods->updateByWhere($postData);
    }

    /**
     * 批量将多个设备商品的独立收款策略整体替换为同一策略集合。
     */
    public function updatePayeeStrategiesBatch()
    {
        $postData = input();
        if (!array_key_exists('sp_ids', $postData)) return returnValidate('收款策略ID列表必须传入');
        try {
            $this->validate($postData, $this->validatePath . '.updatePayeeStrategiesBatch');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineGoods->updatePayeeStrategiesBatch($postData);
    }

    public function del()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . '.del');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineGoods->delMg($postData);
    }

    /**
     * 导出设备商品
     * @return array|\think\response\Json
     */
    public function exportMg()
    {
        $postData = input();
        $hasCostPriceAuth = $this->hasCostPriceAuth();
        $where = $this->getWhere($postData, false, ["g_name" => "like",'sku' => "like"]);
        return $this->app->machineGoods->exportMg($where, $hasCostPriceAuth);
    }

    /**
     * 设备商品库同步商品库价格
     * @return array|\think\response\Json
     */
    public function synchronizationGoods()
    {
        $postData = input();
        return $this->app->machineGoods->synchronizationGoodsPrice($postData);
    }
}
