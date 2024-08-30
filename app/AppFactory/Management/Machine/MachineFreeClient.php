<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/26
 * Time: 15:37
 */

namespace app\AppFactory\Management\Machine;


use app\AppFactory\Kernel\Traits\Machine\MachineFreeGoodsTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineFreeHotelTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineFreeTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\Machine\VMachineFree;

class MachineFreeClient extends ManagementClient
{
    use MachineFreeTrait,MachineFreeGoodsTrait,MachineFreeHotelTrait;

    /**
     * 获取设备的自由组合信息
     * @param $where
     * @param string $field
     * @param string $order
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function getMfFind($where,$field = "*",$order = "")
    {
        $data = $this->getMachineFreeFind($where,$field,$order);
        if ($data) {
            if ($data['designated_hotel'] > 1) {
                $data['hotelList'] = $this->getMachineFreeHotelList(['mf_id' => $data['mf_id']],0);
            }
            if ($data['designated_goods'] > 1) {
                $data['goodsList'] = $this->getMachineFreeGoodsList(['mf_id' => $data['mf_id']],0,"mfg_id,mf_id,mfg.g_id,mfg.sale_amount,rise_fall_ratio,g_name,g_type,pic,gc_id,gc_name,sku,retail_price");
            }
        }
        return $this->r(200,$this->lang("query_success"),$data);
    }

    /**
     * 添加自由组合
     * @param $postData
     * @return bool|string
     */
    public function addMf($postData)
    {
        $hotelList = [];
        $goodsList = [];
        if (isset($postData['hotelList'])) {
            $hotelList = $postData['hotelList'];
            unset($postData['hotelList']);
        }
        if (isset($postData['goodsList'])) {
            $goodsList = $postData['goodsList'];
            unset($postData['goodsList']);
        }
        $flag = [];
        $this->startTrans();
        $mf_id = $this->addMachineFree($postData);
        if ($mf_id) {
            $flag[] = 1;
            if ($hotelList) {
                foreach ($hotelList as $hK => $hV) {
                    try {
                        validate(VMachineFree::class)->scene("addHotelList")->check($hV);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rTryCatch($e->getMessage());
                    }
                    $hV['mf_id'] = $mf_id;
                    $flag[] = $this->addMachineFreeHotel($hV);
                }
            }
            if ($goodsList) {
                foreach ($goodsList as $gK => $gV) {
                    try {
                        validate(VMachineFree::class)->scene("addGoodsList")->check($gV);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rTryCatch($e->getMessage());
                    }
                    $gV['mf_id'] = $mf_id;
                    $flag[] = $this->addMachineFreeGoods($gV);
                }
            }
        }
        $check = $this->checkTrans($this->checkFlag($flag));
        return $check;
    }

    /**
     * 修改自由组合
     * @param $postData
     * @return bool|string
     */
    public function updateMf($postData)
    {
        $hotelList = [];
        $delHotel = "";
        $goodsList = [];
        $delGoods = "";
        if (isset($postData['hotelList'])) {
            $hotelList = $postData['hotelList'];
            unset($postData['hotelList']);
        }
        if (isset($postData['goodsList'])) {
            $goodsList = $postData['goodsList'];
            unset($postData['goodsList']);
        }
        if (isset($postData['delHotel'])) {
            $delHotel = $postData['delHotel'];
            unset($postData['delHotel']);
            if (!$delHotel) return $this->r(100,$this->lang("VMachineFree.delHotel_notEmpty"));
        }
        if (isset($postData['delGoods'])) {
            $delGoods = $postData['delGoods'];
            unset($postData['delGoods']);
            if (!$delGoods) return $this->r(100,$this->lang("VMachineFree.delGoods_notEmpty"));
        }
        $flag = [];
        $this->startTrans();
        $this->updateMachineFree($postData,[],['free_status','designated_hotel','designated_goods','rise_fall_ratio']);
        if ($delHotel) $flag[] = $this->delMachineFreeHotel([['mfh_id','in',$delHotel],'mf_id' => $postData['mf_id']]);
        if ($delGoods) $flag[] = $this->delMachineFreeGoods([["mfg_id","in",$delGoods],'mf_id' => $postData['mf_id']]);
        if ($hotelList) {
            foreach ($hotelList as $hK => $hV) {
                if (isset($hV['mfh_id']) && $hV['mfh_id']) {
                    $flag[] = $this->updateMachineFreeHotel($hV,[],['hotelTel','imageUrl','address','openYear','renovationYear','roomQuantity','guestOverallRating','rise_fall_ratio']);
                } else {
                    try {
                        validate(VMachineFree::class)->scene("addHotelList")->check($hV);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rTryCatch($e->getMessage());
                    }
                    $mfh = $this->getMachineFreeHotelFind(['hotelId' => $hV['hotelId'],'mf_id' => $postData['mf_id']]);
                    if (!$mfh) {
                        $hV['mf_id'] = $postData['mf_id'];
                        $flag[] = $this->addMachineFreeHotel($hV);
                    }
                }
            }
        }
        if ($goodsList) {
            foreach ($goodsList as $gK => $gV) {
                if (isset($gV['mfg_id']) && $gV['mfg_id']) {
                    $flag[] = $this->updateMachineFreeGoods($gV,[],['sale_amount','rise_fall_ratio']);
                } else {
                    try {
                        validate(VMachineFree::class)->scene("addGoodsList")->check($gV);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rTryCatch($e->getMessage());
                    }
                    $mfg = $this->getMachineFreeGoodsFind(['g_id' => $gV['g_id'],'mf_id' => $gV['mf_id']]);
                    if (!$mfg) {
                        $gV['mf_id'] = $postData['mf_id'];
                        $flag[] = $this->addMachineFreeGoods($gV);
                    }
                }
            }
        }
        $check = $this->checkTrans($this->checkFlag($flag));
        return $check;
    }

    /**
     * 删除设备组合数据
     * @param $mf_id
     * @return array|\think\response\Json
     */
    public function delMf($mf_id)
    {
        $this->delMachineFree(['mf_id' => $mf_id]);
        $this->delMachineFreeGoods(['mf_id' => $mf_id]);
        $this->delMachineFreeHotel(['mf_id' => $mf_id]);
        return $this->r(200,$this->lang("action_success"));
    }
}