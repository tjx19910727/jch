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

class WeiChengClient extends ManagementClient
{
    use WcBaseTrait,WcGoodsTrait;

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

    public function getWcRequestLogsInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getWcRequestLogsList($where, $pageNum, $field, $order, $eachFun, $group));
    }

    public function addWcRequestLogsInfo($postData)
    {
        return $this->rA($this->addWcRequestLogs($postData));
    }

    public function updateWcRequestLogsInfo($update, $where = [], $field = [])
    {
        return $this->rU($this->updateWcRequestLogs($update, $where, $field));
    }

    public function delWcRequestLogsInfo($where)
    {
        return $this->rD($this->delWcRequestLogs($where));
    }

    public function synchronizeGoodsTypesAll(){
        $wc_goods_type = $this->getWcGoodsTypesList([['id','>','0']]);
        if(!$wc_goods_type) return true;
        $wc_goods_type = $wc_goods_type->toArray();
        foreach($wc_goods_type as $type){
            $res = $this->app->weicheng->synchronizeGoodsTypes($type);
        }
        return $res;
    }

    public function synchronizeGoodsTypes($goods_type, $nowPage = 1)
    {
        $result = $this->goodsTypesSync($goods_type, $nowPage);
        if ($result['status'] != 200) {
            return $this->rA('分类商品同步失败: ' . $result['response']);
        }
        $updateData = json2arr($result['response']);

        $totalPage = isset($updateData['data']['totalPage']) ? intval($updateData['data']['totalPage']) : 1;
        $goods_lists = $updateData['data']['list'] ?? [];

        $res = $this->synchronizeGoodsLists2Db($goods_lists);

        // 如果还有下一页，递归处理并合并结果
        if ($nowPage < $totalPage) {
            $nextRes = $this->synchronizeGoodsTypes($goods_type, $nowPage + 1);

            // 合并当前页与后续页的结果，尽量兼容各种返回类型
            $current = is_array($res) ? $res : [$res];
            if (is_array($nextRes)) {
                $combined = array_merge($current, $nextRes);
            } else {
                $current[] = $nextRes;
                $combined = $current;
            }
        } else {
            $combined = is_array($res) ? $res : [$res];
        }

        // 仅在顶层调用时返回标准化的 rA 响应，递归内部返回原始合并结果
        if ($nowPage === 1) {
            return $this->rA('分类商品同步成功', $combined);
        }

        return $combined;
    }

    public function synchronizeGoodsAll(){
        $wc_goods = $this->getWcGoodsList(['price' => null])->toArray();
        foreach($wc_goods as $v){
           $res = $this->synchronizeGoods($v['no']);
        //    if($res['status']) return ;
           if(!$res['status']) continue;
        }
        return returnState('200','分类商品同步成功', );;
    }

    public function synchronizeGoods($goods_no)
    {
        $result = $this->goodsSync($goods_no);
        if ($result['status'] == 200) {
            $updateData = json2arr($result['response']);
            if(!$updateData || !isset($updateData['product'])) {
                // actionLog('同步失败', $goods_no);
                return ['status' => false, 'msg' => $result['response']];
            }
            $updateData = $updateData['product'];
            $updateData['resourcesArray'] = json_encode($updateData['resourcesArray'], JSON_UNESCAPED_UNICODE);
            $res = $this->synchronizeGoods2Db($updateData);
            return ['status' => $res];
        } 
        return ['status' => false, 'msg' => $result['response']];;
    }

    public function synchronizeOrder($order)
    {
        // $this->syncOrder($order);
    }

    public function synchronizeOrderRefund($order)
    {
        // $this->syncOrderRefund($order);
    }
}
