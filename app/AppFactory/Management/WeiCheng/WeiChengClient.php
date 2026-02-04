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
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineGoodsTrait;
use app\AppFactory\Management\ManagementClient;

class WeiChengClient extends ManagementClient
{
    use WcBaseTrait,WcGoodsTrait,MachineTrait,MachineGoodsTrait;

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

    public function getWcGoodsLocalInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getWcGoodsLocalList($where, $pageNum, $field, $order, $eachFun, $group));
    }

    public function addWcGoodsLocalInfo($postData)
    {
        return $this->rA($this->addWcGoodsLocal($postData));
    }

    public function updateWcGoodsLocalInfo($update, $where = [], $field = [])
    {
        return $this->rU($this->updateWcGoodsLocal($update, $where, $field));
    }

    public function delWcGoodsLocalInfo($where)
    {
        return $this->rD($this->delWcGoodsLocal($where));
    }

    public function getWcMachineChannelInfoList($where, $pageNum = 0, $field = "*", $order = "", $eachFun = "", $group = "")
    {
        return $this->rQ($this->getWcMachineChannelList($where, $pageNum, $field, $order, $eachFun, $group));
    }

    public function addWcMachineChannelInfo($postData)
    {
        return $this->rA($this->addWcMachineChannel($postData));
    }

    public function updateWcMachineChannelInfo($update, $where = [], $field = [])
    {
        return $this->rU($this->updateWcMachineChannel($update, $where, $field));
    }

    public function delWcMachineChannelInfo($where)
    {
        return $this->rD($this->delWcMachineChannel($where));
    }


    public function synchronizeGoodsTypesAll(){
        $wc_goods_type = $this->getWcGoodsTypesList([['id','>','0']]);
        if(!$wc_goods_type) return true;
        $wc_goods_type = $wc_goods_type->toArray();
        foreach($wc_goods_type as $type){
            $res = $this->app->weicheng->synchronizeGoodsTypes($type['id'], 1);
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

        $res = $this->synchronizeGoodsLists2Db($goods_lists, $goods_type);
        
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
        $wc_goods = $this->getWcGoodsList([['id', '>', '0']])->toArray();
        foreach($wc_goods as $v){
           $res = $this->synchronizeGoods($v['no'], $v['type']);
           if(!$res['status']) continue;
        }   
        $this->wcGoodsWriteLocal();    
        return returnState('200','分类商品同步成功', );;
    }

    public function synchronizeGoods($goods_no, $type)
    {
        $result = $this->goodsSync($goods_no, $type);

        if ($result['status'] == 200) {
            $res = json2arr($result['response']);
            if(!$res || !isset($res['product'])) {
                // actionLog('同步失败', $goods_no);
                return ['status' => false, 'msg' => $result['response']];
            }

            $updateData = $res['product'];
            $updateData['get_data'] = $result['response'];
            if(isset($updateData['goods'])) 
                $updateData['goods'] = json_encode($updateData['goods']);
            if(isset($updateData['combination_goods']))  
                $updateData['combination_goods'] = json_encode($updateData['combination_goods'], JSON_UNESCAPED_UNICODE);
            if(isset($updateData['resourcesArray'])) 
                $updateData['resourcesArray'] = json_encode($updateData['resourcesArray'], JSON_UNESCAPED_UNICODE);
            if(isset($updateData['daysInfo'])) 
                $updateData['daysInfo'] = json_encode($updateData['daysInfo']);
            
            //type值是从goods_type带过来的，这里不要修改商品的type，否则查询不到数据
            if(isset($updateData['type'])) unset($updateData['type']);
            $res = $this->synchronizeGoods2Db($updateData);
            return ['status' => $res];
        } 
        return ['status' => false, 'msg' => $result['response']];;
    }

     public function wcGoodsWriteLocal(){
        $wc_goods = $this->getWcGoodsList([['id', '>', '0']])->toArray();
        foreach($wc_goods as $wc_good){
            $res = $this->setWcGoodsLocal($wc_good['no']);
        }
        return $this->rA('微程商品本地化写入完成');
    }

    //获取设备可排序的微程商品列表
    public function getMachineWcGoodsLists($machine_id, $pageNum){
        $where['machine_id'] = $machine_id;
        $machine_goods_ids = $this->getMachineGoodsColumn($where, 'g_id');
        $wc_goods_local = $this->getWcGoodsLocalList([['g_id','not in', $machine_goods_ids],['type','in', '1,2,3,4,5']], $pageNum,'*', 'id desc')->toArray();
        return  $this->rQ($wc_goods_local);
    }

    //获取设备可排序的微程商品列表
    public function getMachineWcCombinGoodsLists($where, $pageNum){
        $list  = $this->getWcGoodsList($where, $pageNum, '*', 'id desc');
        foreach($list as &$v){
            $v['goods_list'] = $this->getWcGoodsLocalList(['out_no'=> $v['no']])->toArray();
        }
        return  $this->rQ($list);
    }

    //设置虚拟货道商品排序
    public function setWcMachineChannelLists($m_id, $wc_goods_local_ids_arr){
        $machine = $this->getMachineFind(['m_id' => $m_id])->toArray();
        //删除历史记录，重新新增当前排序记录
        $res = $this->delWcMachineChannelInfo(['m_id' => $m_id]);
        $wc_goods_local_lists = $this->getWcGoodsLocalList([['id', 'in', $wc_goods_local_ids_arr]]);
        $wc_goods_type = $this->getWcGoodsTypesList([['id', '>', '0']])->toArray();
        $wc_goods_type_arr = [];
        foreach ($wc_goods_type as $v) {
            $wc_goods_type_arr[$v['id']] = $v['name'];
        }
        if(!$wc_goods_local_lists) return $this->rA('上架失败，找不到微程商品信息');
        $inserData = [];
        $flag = [];
        foreach($wc_goods_local_lists as $wc_goods_local){
            $wc_goods = $this->getWcGoodsFind(['no' => $wc_goods_local['out_no']])->toArray();

            $inserData = [
                'm_id' => $m_id,
                'machine_id' => $machine['machine_id'],
                'channel_code' => 'Z10',
                'g_id' => $wc_goods_local['g_id'],
                'out_no' => $wc_goods_local['out_no'],
                'g_name' => $wc_goods_local['g_name'],
                'gc_id' => $wc_goods_local['g_type'], //  这里传的type应该不是外层type  所以type_name未知
                'gc_name' => '',
                'pic' => $wc_goods_local['pic'],
                'sku' => $wc_goods_local['sku'],
                'bar_code' => $wc_goods_local['sku'],
                'retail_price' => $wc_goods_local['retail_price'],
                'sort' => array_search($wc_goods_local['id'], $wc_goods_local_ids_arr) + 1,
            ];
            $flag[] = $this->addWcMachineChannel($inserData);
        }
        if($this->checkFlag($flag)); return $this->rA('虚拟货道微程商品上架完成');
        return $this->rA('上架失败');
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
