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

    protected $field = "mg_id,g_id,g_name,gc_id,gc_name,gc_sort,pic,sku,cost_price,market_price,retail_price,
    (SELECT sum(mc.stock) FROM machine_channel mc where mc.m_id = a.m_id AND mc.status = 1 AND mc.mg_id = a.mg_id) available_stock,
    (SELECT sum(mc.stock) FROM machine_channel mc where mc.m_id = a.m_id AND mc.status > 1 AND mc.mg_id = a.mg_id) disabled_stock,
    (SELECT sum(mc.frozen_stock) FROM machine_channel mc where mc.m_id = a.m_id AND mc.mg_id = a.mg_id) reserve_stock,
    standby_stock,machine_id,
    (select machine_name FROM machine m WHERE m.m_id = a.m_id) machine_name";
    protected $validatePath = VMachineGoods::class;

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["g_name" => "like",'sku' => "like"]);
        return $this->app->machineGoods->getList($where, $pageNum, $this->field);
    }

    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData, false, []);
        return $this->app->machineGoods->getFind($where, $this->field);
    }

    /**
     * 获取设备商品品类排序列表
     * @return array|\think\response\Json
     */
    public function getGcSort()
    {
        $where['m_id'] = input("m_id");
        return returnData($this->app->machineGoods->getMachineGoodsList($where, 0, "gc_name,gc_id,`gc_sort`", "gc_sort asc", '', 'gc_id'));
    }

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

    public function updateMoreByWhere()
    {
        $postData = input();
        $postData = json2arr($postData);
        if (!isset($postData['where']) || !$postData['where']) return returnState(100, lang("where_require"));
        if (!isset($postData['update']) || !$postData['update']) return returnState(100, lang("update_require"));
        return $this->app->machineGoods->updateByWhere($postData);
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
}