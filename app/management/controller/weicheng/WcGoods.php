<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/1/5
 * Time: 08:50
 */

namespace app\management\controller\weicheng;

use app\AppFactory\AppFactory;
use app\management\controller\Common;

class WcGoods extends Common 
{

    public function syncAll()
    {
        return $this->app->weicheng->synchronizeGoodsAll();
    }

    public function sync()
    {
        $goods_no = input('goods_no');
        $type = input('type');
        $res = $this->app->weicheng->synchronizeGoods($goods_no, $type);
        if($res['status']) return returnState(200,'success','同步成功');
        return returnState(100,'fail',$res['msg']);
    }
    
   
    public function getList(){
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['type' => 'like','name' => 'like','no' => 'like']);
        return $this->app->weicheng->getWcGoodsInfoList($where, $pageNum, "*", 'id desc');
    }

    public function setWcGoodsLocal(){
        return $this->app->weicheng->wcGoodsWriteLocal();
    }
   
    public function getWcPhysicalGoodsLists(){
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 15;
        $where = $this->getWhere($postData, false, ['type' => 'like','name' => 'like','no' => 'like']);
        return $this->app->weicheng->getWcPhysicalGoodsLists($where, $pageNum);
    }

    public function getWcCombinGoodsLists(){
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['type' => 'like','name' => 'like','no' => 'like']);
        $where['type'] = 11; //组合商品
        return $this->app->weicheng->getWcCombinGoodsLists($where, $pageNum, "*", 'id desc');
    }

    //多对多微程商品与设备绑定
    public function setWcMachineGoodsLists(){
        $postData = input();
        $m_ids = $postData['m_ids'] ?? 0;
        $out_nos = $postData['out_nos'] ?? [];
        $m_ids = is_array($m_ids) ? $m_ids : explode(',', $m_ids);
        $out_nos = is_array($out_nos) ? $out_nos : explode(',', $out_nos);
        return $this->app->weicheng->setWcMachineGoodsBatchLists($m_ids, $out_nos);
    }

    public function getWcMachineGoodsLists(){
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['machine_id' => 'like']);
        return $this->app->weicheng->getWcMachineGoodsLists($where, $pageNum, "*", 'id desc');
    }
}