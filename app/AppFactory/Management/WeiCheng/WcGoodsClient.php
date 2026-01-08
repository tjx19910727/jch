<?php
/**
 * Created by VSCode.
 * User: Alex-jixiang
 * Date: 2026/1/05
 * Time: 15:42
 */

namespace app\AppFactory\Management\WeiCheng;

use app\AppFactory\Kernel\Traits\WeiCheng\WcBaseTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcGoodsTrait;
use app\AppFactory\Management\ManagementClient;

class WcGoodsClient extends ManagementClient
{
    use WcBaseTrait, WcGoodsTrait;

    public function getWcGoodsInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getWcGoodsList($where, $pageNum, $field, $order, $eachFun, $group));
    }

    public function addWcGoodsInfo($postData)
    {
        return $this->rA($this->addWcGoods($postData));
    }

    public function updateWcGoodsInfo($update, $where = [], $field = [])
    {
        return $this->rU($this->updateWcGoods($update, $where, $field));
    }

    public function delWcGoodsInfo($where)
    {
        return $this->rD($this->delWcGoods($where));
    }

    public function synchronizeGoods($goods_no)
    {
       $result = $this->goodsSync($goods_no);
       if ($result['status'] == 200) {
           $res = $this->synchronizeGoods2Db($result['response']); 
           return $this->rS('Synchronization successful', $res);
           return                                                                                                                                                                                                                $res;
       } else {
           return $this->rE('Synchronization failed: '.$result['response']);
       }
    }
}