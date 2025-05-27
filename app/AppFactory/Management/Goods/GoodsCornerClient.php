<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/4/1
 * Time: 11:35
 */

namespace app\AppFactory\Management\Goods;


use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityMachineTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsCornerTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;

class GoodsCornerClient extends ManagementClient
{
    use GoodsCornerTrait,ActivityGoodsTrait,ActivityMachineTrait;
    use MachineTrait,GoodsTrait;

    /**
     * 获取商品角标列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return array|string
     */
    public function getCornerAgAmList($where,$pageNum = 0, $field = "*", $order = "")
    {
        return $this->rQ($this->getGoodsCornerList($where,$pageNum,$field,$order,function ($corner) {
            $update = [];
            if ($corner['start_time'] < time() && $corner['status'] == 1) {
                $corner['status'] = 2;
                $update['status'] = 2;
            }
            if ($corner['end_time'] > 0 && $corner['end_time'] < time() && ($corner['status'] < 3)) {
                $corner['status'] = 3;
                $update['status'] = 3;
            }
            if ($update) {
                $update['id'] = $corner['id'];
                $this->updateGoodsCorner($update);
            }
            $whereA = ['a_type' => 5, "a_id" => $corner['id']];
            $corner['goodsList'] = $this->getActivityGoodsList($whereA,0,'ag_id,g_id,g_name,sku,market_price,retail_price');
            $corner['machineList'] = $this->getActivityMachineList($whereA,0,'am_id,m_id,machine_id,machine_name');
            return $corner;
        }));
    }

    /**
     * 获取一条商品角标
     * @param $where
     * @param string $field
     * @return array|string
     */
    public function getCornerAgAmFind($where,$field = "*")
    {
        $corner = $this->getGoodsCornerFind($where,$field);
        if ($corner) {
            $whereA = ['a_type' => 5, "a_id" => $corner['id']];
            $corner['goodsList'] = $this->getActivityGoodsList($whereA,0,'ag_id,g_id,g_name,sku,market_price,retail_price');
            $corner['machineList'] = $this->getActivityMachineList($whereA,0,'am_id,m_id,machine_id,machine_name');
        }
        return $this->rQ($corner);
    }

    /**
     * 添加商品角标
     * @param $postData
     * @return array|string
     */
    public function addCorner($postData)
    {
        $goodsList = explode(",",$postData['goodsList']);
        $machineList = explode(",",$postData['machineList']);
        unset($postData['goodsList'],$postData['machineList']);
        $this->startTrans();
        try {
            $id = $this->addGoodsCorner($postData);
            if ($id) {
                $insert = [
                    "a_id" => $id,
                    "a_type" => 5,
                ];
                if ($goodsList) {
                    $agResult = $this->addAg($insert, $goodsList);
                    if ($agResult !== true) {
                        $this->rollbackTrans();
                        return $this->rFail($agResult);
                    }
                }
                if ($machineList) {
                    $amResult = $this->addAm($insert, $machineList);
                    if ($amResult !== true) {
                        $this->rollbackTrans();
                        return $this->rFail($amResult);
                    }
                    foreach ($machineList as $mv) {
                        // 发送触发角标更新数据
                        $this->sendToMachine(['machine_id' => $mv],'changeCorner');
                    }
                }
                $this->commitTrans();
                return $this->r(200, $this->lang("add_success"));
            }
            $this->rollbackTrans();
            return $this->rFail($this->lang("add_fail"));
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 修改商品角标
     * @param $postData
     * @return array|bool|string
     */
    public function updateCorner($postData)
    {
        $goodsList = explode(",",$postData['goodsList']);
        $machineList = explode(",",$postData['machineList']);
        unset($postData['goodsList'],$postData['machineList']);
        $flag = [];
//        $this->startTrans();
        $flag[] = $this->updateGoodsCorner($postData);
        $insert = [
            "a_id" => $postData['id'],
            "a_type" => 5,
        ];
        if ($machineList) {
            $oldAmList = $this->getActivityMachineColumn(['a_id' => $postData['id'],'a_type' => 5],'machine_id');
            $delAmList = array_diff($oldAmList,$machineList);
            $addAmList = array_diff($machineList,$oldAmList);
            if ($addAmList) {
                $amResult = $this->addAm($insert, $addAmList);
                if ($amResult !== true) {
//                    $this->rollbackTrans();
                    return $this->rFail($amResult);
                }
                foreach ($addAmList as $mv) {
                    // 发送触发角标更新数据
                    $this->sendToMachine(['machine_id' => $mv],'changeCorner');
                }
                $flag[] = 1;
            }
            if ($delAmList) {
                $flag[] = $this->delActivityMachine(['a_id' => $postData['id'],'a_type' => 5, ['machine_id','in', $delAmList]]);
                foreach ($delAmList as $mdv) {
                    // 发送触发角标更新数据
                    $this->sendToMachine(['machine_id' => $mdv],'changeCorner');
                }
            }
        }
        if ($goodsList) {
            $oldAgList = $this->getActivityGoodsColumn(['a_id' => $postData['id'],'a_type' => 5],'g_id');
            $delAgList = array_diff($oldAgList,$goodsList);
            $addAgList = array_diff($goodsList,$oldAgList);
            if ($addAgList) {
                $agResult = $this->addAg($insert, $goodsList);
                if ($agResult !== true) {
//                    $this->rollbackTrans();
                    return $this->rFail($agResult);
                }
                $flag[] = 1;
            }
            if ($delAgList) $flag[] = $this->delActivityGoods(['a_id' => $postData['id'],'a_type' => 5,['g_id','in',$delAgList]]);
        }
        $check = $this->checkFlag($flag);
        return $this->rAction($check);
    }

    /**
     * 删除角标，同时删除关联的商品与设备
     * @param $id
     * @return array|string
     */
    public function delCorner($id)
    {
        $machine_ids = $this->getActivityMachineColumn(['a_type' => 5,['a_id','in',$id]],'machine_id');
        foreach ($machine_ids as $mdv) {
            // 发送触发角标更新数据
            $this->sendToMachine(['machine_id' => $mdv],'changeCorner');
        }
        $this->delGoodsCorner([['id',"in",$id]]);
        $where[] = ['a_id','in',$id];
        $where['a_type'] = 5;
        $this->delActivityGoods($where);
        $this->delActivityMachine($where);
        return $this->rSuccess();
    }

    /**
     * 下架角标
     * @param $where
     * @return array|\think\response\Json
     */
    public function cornerTakeDown($where)
    {
        $a_id = $this->getGoodsCornerColumn($where,'id');
        $result = $this->updateGoodsCorner(["status" => 4],$where);
        if ($result) {
            $machine_ids = $this->getActivityMachineColumn(['a_type' => 5,['a_id','in',$a_id]],'machine_id');
            foreach ($machine_ids as $mdv) {
                // 发送触发角标更新数据
                $this->sendToMachine(['machine_id' => $mdv],'changeCorner');
            }
        }
        return $this->rAction($result);
    }
}