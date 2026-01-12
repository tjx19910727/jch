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
    public function synchronizeGoods()
    {
        $goods_no = input('goods_no');
        $res = $this->app->weicheng->synchronizeGoods($goods_no);
        return $this->rS($res);
    }
   
    public function getList(){
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ['type' => 'like','name' => 'like','no' => 'like']);
        return $this->app->WcGoods->getWcGoodsInfoList($where, $pageNum, "*", 'id desc');
    }
}