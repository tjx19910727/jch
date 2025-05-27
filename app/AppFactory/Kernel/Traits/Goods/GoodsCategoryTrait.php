<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 20:05
 */

namespace app\AppFactory\Kernel\Traits\Goods;


use app\AppFactory\Kernel\Model\Activity\ActivityGoodsModel;
use app\AppFactory\Kernel\Model\Goods\GoodsCategoryModel;
use app\AppFactory\Kernel\Model\Goods\GoodsModel;
use app\AppFactory\Kernel\Model\Machine\MachineChannelModel;
use app\AppFactory\Kernel\Model\Machine\MachineGoodsModel;

trait GoodsCategoryTrait
{
    public function getGoodsCategoryValue($where,$value)
    {
        return GoodsCategoryModel::getFieldValue($where,$value);
    }

    public function getGoodsCategoryColumn($where,$column)
    {
        return GoodsCategoryModel::getColumn($where,$column);
    }

    public function getGoodsCategoryFind($where,$field = "*",$order = "")
    {
        return GoodsCategoryModel::getFind($where,$field,$order);
    }

    public function getGoodsCategoryList($where,$pageNum = 0,$field = "*", $order = "",$eachFun = "")
    {
        return GoodsCategoryModel::getList($where,$pageNum,$field,$order,$eachFun);
    }

    public function addGoodsCategory($insert)
    {
        $insert['creator'] = $this->manager['manager_id'];
        $data = GoodsCategoryModel::create($insert);
        $pk = $data->getPk();
        return $data->$pk;
    }

    public function updateGoodsCategory($update,$where = [],$field = [])
    {
        $update['update_id'] = $this->manager['manager_id'];
        $result = GoodsCategoryModel::update($update,$where,$field);
        if ($result) {
            if (isset($result['gc_name'])) {
                GoodsModel::update(['gc_name' => $result['gc_name']],['gc_id' => $result['gc_id']]);
                MachineGoodsModel::update(['gc_name' => $result['gc_name']],['gc_id' => $result['gc_id']]);
                MachineChannelModel::update(['gc_name' => $result['gc_name']],['gc_id' => $result['gc_id']]);
                ActivityGoodsModel::update(['gc_name' => $result['gc_name']],['gc_id' => $result['gc_id']]);
            }
        }
        return $result;
    }

    /**
     * 删除商品分类，包括分类下级
     * @param $where
     * @return bool
     */
    public function delGoodsCategory($where)
    {
        $gc = $this->getGoodsCategoryFind($where,'gc_id');
        $childIds = $this->getGcChildId($gc['gc_id']);
        $result = GoodsCategoryModel::whereDel($where);
        GoodsCategoryModel::whereDel([['gc_id','in',$childIds]]);
        return $result;
    }

    /**
     * 查找分类上级ID列表
     * @param $pid
     * @param string $field
     * @return array
     */
    public function getGcParent($pid,$field = "*")
    {
        $list = [];
        $parent = $this->getGoodsCategoryFind(['gc_id' => $pid],$field);
        if ($parent) {
            $parent = $parent->toArray();
            $list[] = $parent;
            if (isset($parent['gc_pid']) && $parent['gc_pid'] > 0) {
                $list = array_merge($list,$this->getGcParent($parent['gc_pid'],$field));
            }
        }
        return $list;
    }

    /**
     * 查找分类下级ID列表
     * @param $gc_id
     * @param array $ids
     * @return array
     */
    private function getGcChildId($gc_id,$ids = [])
    {
        $childIds = $this->getGoodsCategoryColumn(['gc_pid' => $gc_id],'gc_id');
        if ($childIds) {
            $ids = array_merge($ids,$childIds);
            foreach ($childIds as $item) {
                $ids = $this->getGcChildId($item['gc_id'],$ids);
            }
        }
        return array_unique($ids);
    }
}