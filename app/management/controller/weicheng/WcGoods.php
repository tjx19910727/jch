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

    public function getLocalLists(){
        $postData = input();
        $pageNum = input('pageNum') ?? 15;
        $where = $this->getWhere($postData, false, ['g_name' => 'like']);
        return $this->app->weicheng->getWcGoodsLocalInfoList($where, $pageNum, "*", 'id desc');
    }
}