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
use app\AppFactory\Kernel\Traits\WeiCheng\WcGoodsTypesTrait;
use app\AppFactory\Kernel\Traits\WeiCheng\WcRequestLogsTrait;
use app\AppFactory\Management\ManagementClient;

class WeiChengClient extends ManagementClient
{
    use WcBaseTrait, WcGoodsTrait,WcGoodsTypesTrait,WcRequestLogsTrait;

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

     public function getWcGoodsTypesInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getWcGoodsTypesList($where, $pageNum, $field, $order, $eachFun, $group));
    }

    public function addWcGoodsTypesInfo($postData)
    {
        return $this->rA($this->addWcGoodsTypes($postData));
    }

    public function updateWcGoodsTypesInfo($update, $where = [], $field = [])
    {
        return $this->rU($this->updateWcGoodsTypes($update, $where, $field));
    }

    public function delWcGoodsTypesInfo($where)
    {
        return $this->rD($this->delWcGoodsTypes($where));
    }

    

    public function getWcRequestLogsList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getWcRequestLogsList($where, $pageNum, $field, $order, $eachFun, $group));
    }
    
    public function addWcRequestLogs($postData)
    {
        return $this->rA($this->addWcRequestLogs($postData));
    }

    public function updateWcRequestLogs($update, $where = [], $field = [])
    {
        return $this->rU($this->updateWcRequestLogs($update, $where, $field));
    }

    public function delWcRequestLogs($where)
    {
        return $this->rD($this->delWcRequestLogs($where));
    }

    public function synchronizeGoodsTypes()
    {
       
    }


    public function synchronizeGoods($goods_no)
    {
       $result = $this->goodsSync($goods_no);
       if ($result['status'] == 200) {
           $res = $this->synchronizeGoods2Db($result['response']); 
           return $this->rA('Synchronization successful', $res);
           return                                                                                                                                                                                                                $res;
       } else {
           return $this->rA('Synchronization failed: '.$result['response']);
       }
    }

    public function synchronizeOrder($order){
        $this->syncOrder($order);
    }

    public function synchronizeOrderRefund($order){
        $this->syncOrderRefund($order);
    }
}