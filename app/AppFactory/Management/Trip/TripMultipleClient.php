<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/9/9
 * Time: 17:07
 */

namespace app\AppFactory\Management\Trip;


use app\AppFactory\Kernel\Traits\Trip\TripMultipleGoodsTrait;
use app\AppFactory\Kernel\Traits\Trip\TripMultipleHotelTrait;
use app\AppFactory\Kernel\Traits\Trip\TripMultipleMachineTrait;
use app\AppFactory\Kernel\Traits\Trip\TripMultipleTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\Trip\VTripMultipleGoods;
use app\management\validate\Trip\VTripMultipleHotel;
use app\management\validate\Trip\VTripMultipleMachine;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class TripMultipleClient extends ManagementClient
{
    use TripMultipleTrait,TripMultipleGoodsTrait,TripMultipleHotelTrait,TripMultipleMachineTrait;

    /**
     * 获取列表
     * @param $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @return \app\AppFactory\Kernel\Model\BaseModel|\app\AppFactory\Kernel\Model\BaseModel[]|array|string|\think\Collection|\think\Paginator|\think\response\Json
     */
    public function getTmList($where,$pageNum = 0, $field = "*", $order = "")
    {
        try {
            if ($pageNum) {
                $data = $this->getTripMultipleList($where, $pageNum, $field, $order)->each(function ($value) {
                    $whereTm['tm_id'] = $value['tm_id'];
                    $tmhField = "tmh_id,tc_id,cityId,cityName,hotelId,hotelName,hotelTel,imageUrl,address,openYear,renovationYear,roomQuantity,guestOverallRating,rise_fall_ratio,is_require";
                    $value['hotelList'] = $this->getTripMultipleHotelList($whereTm, 0,$tmhField);
                    $tmgField = "tmg_id,tmg.is_required,tmg.buy_lower,tmg.buy_upper,tmg.sale_amount,tmg.rise_fall_ratio,tmg.g_id,g.g_name,g.pic,g.gc_name,g.g_type,g.model,g.sku,g.performance,g.status,g.retail_price";
                    $value['goodsList'] = $this->getTripMultipleGoodsJoinGoodsList($whereTm, 0, $tmgField);
                    $value['machineList'] = $this->getTripMultipleMachineList($whereTm, 0, 'tmm_id,m_id,machine_id,machine_name');
                    return $value;
                });
            } else {
                $data = $this->getTripMultipleList($where, 0, $field, $order);
            }
            return $this->r(200,$this->lang("query_success"),$data);
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 获取一条详情
     * @param $where
     * @param string $field
     * @param string $order
     * @return array|\think\response\Json
     */
    public function getTmFind($where,$field = "*",$order = "")
    {
        try {
            $value = $this->getTripMultipleFind($where, $field, $order);
            if ($value) {
                $whereTm['tm_id'] = $value['tm_id'];
                $tmhField = "tmh_id,tc_id,cityId,cityName,hotelId,hotelName,hotelTel,imageUrl,address,openYear,renovationYear,roomQuantity,guestOverallRating,rise_fall_ratio,is_require";
                $value['hotel'] = $this->getTripMultipleHotelList($whereTm,0, $tmhField);
                $tmgField = "tmg_id,tmg.is_required,tmg.buy_lower,tmg.buy_upper,tmg.sale_amount,tmg.rise_fall_ratio,tmg.g_id,g.g_name,g.pic,g.gc_name,g.g_type,g.model,g.sku,g.performance,g.status,g.retail_price";
                $value['goods'] = $this->getTripMultipleGoodsJoinGoodsList($whereTm, 0, $tmgField);
                $value['machine'] = $this->getTripMultipleMachineList($whereTm, 0, 'tmm_id,m_id,machine_id,machine_name');
            }
            return $this->r(200, $this->lang("query_success"), $value);
        } catch (DataNotFoundException $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        } catch (ModelNotFoundException $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        } catch (DbException $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    /**
     * 添加携程套餐商品
     * @param $postData
     * @return array|bool|string|\think\response\Json
     */
    public function addTm($postData)
    {
        $hotelList = [];
        $goodsList = [];
        $machineList = [];
        if (isset($postData['hotelList'])) {
            $hotelList = $postData['hotelList'];
            unset($postData['hotelList']);
        }
        if (isset($postData['goodsList'])) {
            $goodsList = $postData['goodsList'];
            unset($postData['goodsList']);
        }
        if (isset($postData['machineList'])) {
            $machineList = $postData['machineList'];
            unset($postData['machineList']);
        }
        $flag = [];
        $this->startTrans();
        $tm_id = $this->addTripMultiple($postData);
        if ($tm_id) {
            $flag[] = 1;
            if ($hotelList) {
                foreach ($hotelList as $hK => $hV) {
                    try {
                        validate(VTripMultipleHotel::class)->scene("add")->check($hV);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rTryCatch($e->getMessage());
                    }
                    $hV['tm_id'] = $tm_id;
                    $flag[] = $this->addTripMultipleHotel($hV);
                }
            }
            if ($goodsList) {
                foreach ($goodsList as $gK => $gV) {
                    try {
                        validate(VTripMultipleGoods::class)->scene("add")->check($gV);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rTryCatch($e->getMessage());
                    }
                    $gV['tm_id'] = $tm_id;
                    $flag[] = $this->addTripMultipleGoods($gV);
                }
            }
            if ($machineList) {
                foreach ($machineList as $mK => $mV) {
                    try {
                        validate(VTripMultipleMachine::class)->scene("add")->check($mV);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rTryCatch($e->getMessage());
                    }
                    $mV['tm_id'] = $tm_id;
                    $flag[] = $this->addTripMultipleMachine($mV);
                }
            }
        }
        $check = $this->checkTrans($this->checkFlag($flag));
        return $check;
    }

    /**
     * 修改
     * @param $postData
     * @return array|bool|string|\think\response\Json
     */
    public function updateTm($postData)
    {
        $hotelList = [];
        $delHotel = "";
        $goodsList = [];
        $delGoods = "";
        $machineList = [];
        $delMachine = "";
        if (isset($postData['hotelList'])) {
            $hotelList = $postData['hotelList'];
            unset($postData['hotelList']);
        }
        if (isset($postData['goodsList'])) {
            $goodsList = $postData['goodsList'];
            unset($postData['goodsList']);
        }
        if (isset($postData['machineList'])) {
            $machineList = $postData['machineList'];
            unset($postData['machineList']);
        }
        if (isset($postData['delHotel'])) {
            $delHotel = $postData['delHotel'];
            unset($postData['delHotel']);
            if (!$delHotel) return $this->r(100,$this->lang("VTripMultiple.delHotel_notEmpty"));
        }
        if (isset($postData['delGoods'])) {
            $delGoods = $postData['delGoods'];
            unset($postData['delGoods']);
            if (!$delGoods) return $this->r(100,$this->lang("VTripMultiple.delGoods_notEmpty"));
        }
        if (isset($postData['delMachine'])) {
            $delMachine = $postData['delMachine'];
            unset($postData['delMachine']);
            if (!$delMachine) return $this->r(100,$this->lang("VTripMultiple.delMachine_notEmpty"));
        }
        $flag = [];
        $this->startTrans();
        $this->updateTripMultiple($postData,[],['tm_name','status','designated_hotel','designated_goods','designated_machine','rise_fall_ratio']);
        if ($delHotel) $flag[] = $this->delTripMultipleHotel([['tmh_id','in',$delHotel],'tm_id' => $postData['tm_id']]);
        if ($delGoods) $flag[] = $this->delTripMultipleGoods([["tmg_id","in",$delGoods],'tm_id' => $postData['tm_id']]);
        if ($delMachine) $flag[] = $this->delTripMultipleMachine([["tmm_id","in",$delMachine],'tm_id' => $postData['tm_id']]);
        if ($hotelList) {
            foreach ($hotelList as $hK => $hV) {
                if (isset($hV['tmh_id']) && $hV['tmh_id']) {
                    $flag[] = $this->updateTripMultipleHotel($hV,[],['hotelTel','imageUrl','address','openYear','renovationYear','roomQuantity','guestOverallRating','rise_fall_ratio']);
                } else {
                    try {
                        validate(VTripMultipleHotel::class)->scene("add")->check($hV);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rTryCatch($e->getMessage());
                    }
                    $tmh = $this->getTripMultipleHotelFind(['hotelId' => $hV['hotelId'],'tm_id' => $postData['tm_id']]);
                    if (!$tmh) {
                        $hV['tm_id'] = $postData['tm_id'];
                        $flag[] = $this->addTripMultipleHotel($hV);
                    }
                }
            }
        }
        if ($goodsList) {
            foreach ($goodsList as $gK => $gV) {
                if (isset($gV['tmg_id']) && $gV['tmg_id']) {
                    $flag[] = $this->updateTripMultipleGoods($gV,[],['is_required','buy_lower','buy_upper','sale_amount','rise_fall_ratio']);
                } else {
                    try {
                        validate(VTripMultipleGoods::class)->scene("add")->check($gV);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rTryCatch($e->getMessage());
                    }
                    $tmg = $this->getTripMultipleGoodsFind(['g_id' => $gV['g_id'],'tm_id' => $postData['tm_id']]);
                    if (!$tmg) {
                        $gV['tm_id'] = $postData['tm_id'];
                        $flag[] = $this->addTripMultipleGoods($gV);
                    }
                }
            }
        }
        if ($machineList) {
            foreach ($machineList as $mK => $mV) {
                if (isset($mV['tmm_id']) && $mV['tmm_id']) {
                    $flag[] = $this->updateTripMultipleMachine($mV,[],["machine_name"]);
                } else {
                    try {
                        validate(VTripMultipleMachine::class)->scene("add")->check($mV);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rTryCatch($e->getMessage());
                    }
                    $tmm = $this->getTripMultipleMachineFind(['m_id' => $mV['m_id'],'tm_id' => $postData['tm_id']]);
                    if (!$tmm) {
                        $mV['tm_id'] = $postData['tm_id'];
                        $flag[] = $this->addTripMultipleMachine($mV);
                    }
                }
            }
        }
        $check = $this->checkTrans($this->checkFlag($flag));
        return $check;
    }

    /**
     * 删除设备组合数据
     * @param $tm_id
     * @return array|\think\response\Json
     */
    public function delTm($tm_id)
    {
        $where['tm_id'] = $tm_id;
        $this->delTripMultipleMachine($where);
        $this->delTripMultipleGoods($where);
        $this->delTripMultipleHotel($where);
        $this->delTripMultiple($where);
        return $this->r(200,$this->lang("action_success"));
    }
}