<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/19
 * Time: 10:20
 */

namespace app\AppFactory\Management\Goods;


use app\AppFactory\Kernel\Traits\Goods\GoodsMultipleGoodsTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsMultipleMachineTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsMultipleTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\Goods\VGoodsMultiple;

class GoodsMultipleClient extends ManagementClient
{
    use GoodsMultipleTrait,GoodsMultipleGoodsTrait,GoodsMultipleMachineTrait;
    use GoodsTrait,MachineTrait;

    /**
     * 获取组合商品列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function getGmList($where,$pageNum = 0,$field = "*", $order = "")
    {
        $list = $this->getGoodsMultipleList($where,$pageNum,$field,$order);
        if ($list) {
            if ($pageNum) {
                $list->each(function ($value) {
                    $value['gList'] = $this->getGoodsMultipleGoodsList(['gm_id' => $value['gm_id']],0,'gmg_id,gm_id,sku,g_id,selling_price,rise_fall_ratio');
                    if ($value['gList']) {
                        $value['gList'] = $value['gList']->toArray();
                        $temp = [];
                        foreach ($value['gList'] as $gk => $gv) {
                            $goods = $this->getGoodsFind(['g_id' => $gv['g_id']],'g_name,g_type,sku,pic,cost_price,market_price,performance');
                            $goods = $goods->toArray();
                            $temp[] = array_merge($gv,$goods);
                        }
                        $value["gList"] = $temp;
                    }
                    $value['mList'] = $this->getGoodsMultipleMachineList(['gm_id' => $value['gm_id']],0,'gmm_id,m_id,machine_id,machine_name');
                    return $value;
                });
            }
        }
        return $this->rQ($list);
    }

    /**
     * 获取一条组合商品信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function getGmFind($where,$field = "*", $order = "")
    {
        $gm = $this->getGoodsMultipleFind($where,$field,$order);
        if (!$gm) return $this->rFail($this->lang("query_no_data"));
        $gList = $this->getGoodsMultipleGoodsList(['gm_id' => $gm['gm_id']],0,'*');
        if ($gList) {
            $gList = $gList->toArray();
            foreach ($gList as $gk => $gv) {
                $goods = $this->getGoodsFind(['g_id' => $gv['g_id']],'g_name,g_type,sku,pic,cost_price,market_price,performance');
                $goods = $goods->toArray();
                $gList[$gk] = array_merge($gv,$goods);
            }
        }
        $mList = $this->getGoodsMultipleMachineList(['gm_id' => $gm['gm_id']],0,'*');
        $gm['gList'] = $gList;
        $gm['mList'] = $mList;
        return $this->rQ($gm);
    }

    /**
     * 添加组合商品
     * @param $postData
     * @return array|bool|string|\think\response\Json
     */
    public function addGm($postData)
    {
        $insertGm = [
            "gm_name" => $postData['gm_name'],
            "gm_pic" => $postData['gm_pic'],
            "gm_desc" => $postData['gm_desc'],
            "start_time" => $postData['start_time'],
            "end_time" => $postData['end_time'],
            "ao_id" => $postData['ao_id'] ?? $this->manager['ao_id'],
        ];
        $flag = [];
        $this->startTrans();
        $gm_id = $this->addGoodsMultiple($insertGm);
        if ($gm_id) {
            foreach ($postData['gList'] as $gk => $gv) {
                $gv['gm_id'] = $gm_id;
                try {
                    validate(VGoodsMultiple::class)->scene("gList")->check($gv);
                } catch (\Exception $e) {
                    actionException($e,1);
                    $this->rollbackTrans();
                    return $this->rTryCatch($e->getMessage());
                }
                $insertGmg = $gv;
                $flag[] = $this->addGoodsMultipleGoods($insertGmg);
            }
            foreach ($postData['mList'] as $mk => $mv) {
                $mv['gm_id'] = $gm_id;
                try {
                    validate(VGoodsMultiple::class)->scene("mList")->check($mv);
                } catch (\Exception $e) {
                    actionException($e,1);
                    $this->rollbackTrans();
                    return $this->rTryCatch($e->getMessage());
                }
                $insertGmm = $mv;
                $flag[] = $this->addGoodsMultipleMachine($insertGmm);
            }
        }
        return $this->checkTrans($this->checkFlag($flag));
    }

    /**
     * 修改组合商品数据
     * @param $postData
     * @return array|bool|string|\think\response\Json
     */
    public function updateGm($postData)
    {
        $gList = [];
        $mList = [];
        $delG = "";
        $delM = "";
        if (isset($postData['delG'])) {
            $delG = $postData['delG'];
            unset($postData['delG']);
        }
        if (isset($postData['delM'])) {
            $delM = $postData['delM'];
            unset($postData['delM']);
        }
        if (isset($postData['gList'])) {
            $gList = $postData['gList'];
            unset($postData['gList']);
        }
        if (isset($postData['mList'])) {
            $mList = $postData['mList'];
            unset($postData['mList']);
        }
        $flag = [];
        $this->startTrans();
        if ($postData) $flag[] = $this->updateGoodsMultiple($postData);
        if ($delG) $flag[] = $this->delGoodsMultipleGoods([['gmg_id','in',$delG]]);
        if ($delM) $flag[] = $this->delGoodsMultipleMachine([['gmm_id','in',$delM]]);
        if ($gList) {
            foreach ($gList as $gk => $gv) {
                if (isset($gv['gmg_id']) && $gv['gmg_id']) {
                    $flag[] = $this->updateGoodsMultipleGoods($gv);
                } else {
                    try {
                        validate(VGoodsMultiple::class)->scene("gList")->check($gv);
                    } catch (\Exception $e) {
                        actionException($e,1);
                        $this->rollbackTrans();
                        return $this->rTryCatch($e->getMessage());
                    }
                    $gmg = $this->getGoodsMultipleGoodsFind(['gm_id' => $gv["gm_id"],'g_id' => $gv['g_id']]);
                    if (!$gmg) {
                        $insertGmg = $gv;
                        $flag[] = $this->addGoodsMultipleGoods($insertGmg);
                    }
                }
            }
        }
        if ($mList) {
            foreach ($mList as $mk => $mv) {
                try {
                    validate(VGoodsMultiple::class)->scene("mList")->check($mv);
                } catch (\Exception $e) {
                    actionException($e,1);
                    $this->rollbackTrans();
                    return $this->rTryCatch($e->getMessage());
                }
                $gmm = $this->getGoodsMultipleMachineFind(['gm_id' => $mv['gm_id'],'m_id' => $mv['m_id']],'gmm_id');
                if (!$gmm) {
                    $insertGmm = $mv;
                    $flag[] = $this->addGoodsMultipleMachine($insertGmm);
                }
            }
        }
        return $this->checkTrans($this->checkFlag($flag));
    }

    /**
     * 删除组合商品数据
     * @param $gm_id
     * @return array|\think\response\Json
     */
    public function delGm($gm_id)
    {
        $this->delGoodsMultiple(["gm_id" => $gm_id]);
        $this->delGoodsMultipleMachine(['gm_id' => $gm_id]);
        $this->delGoodsMultipleGoods(['gm_id' => $gm_id]);
        return $this->rSuccess();
    }
}