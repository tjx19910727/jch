<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/12/6
 * Time: 14:15
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityHgGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityHgTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityTimeTrait;
use app\AppFactory\Kernel\Traits\Goods\GoodsTrait;
use app\AppFactory\Kernel\Traits\Store\StoreShelvesTrait;
use app\AppFactory\Kernel\Traits\Store\StoreTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\VActivity;

class ActivityHgClient extends ManagementClient
{
    use ActivityHgTrait,
        ActivityHgGoodsTrait,
        ActivityTimeTrait,
        ActivityGoodsTrait,
        StoreTrait,StoreShelvesTrait,GoodsTrait;


    /**
     * 查询加价换购详情
     * @param $where
     * @param string $field
     * @param string $order
     * @return array|string
     */
    public function getDetails($where,$field = "*", $order = "ah_id desc")
    {
        try {
            $data = $this->getActivityHgFind($where,$field,$order);
            if ($data) {
                $data['timeList'] = $this->getActivityTimeList(['a_id' => $data->ah_id, 'a_type' => 2], 0, 'at_id,start_time,end_time', 'start_time asc');
                $data['goodsList'] = $this->getActivityGoodsList(['a_id' => $data->ah_id, 'a_type' => 2], 0, 'ag_id,store_id,ss_id,shelves_number,wg_id,goods_id,goods_name,goods_pic,goods_c_id,goods_c_name', 'ag_id desc');
                $data['hgGoodsList'] = $this->getActivityHgGoodsList(['ah_id' => $data->ah_id],0,'ahg_id,full_order_amount,wg_id,goods_id,goods_name,goods_pic,goods_c_id,goods_c_name,amount,limit_num','full_order_amount asc');
            }
            return $this->rQ($data);
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }


    /**
     * 添加加价换购活动
     * @param $data
     * @return array|string
     */
    public function addInfo($data)
    {
        try {
            $goodsList = json2arr($data['goodsList']);
            $timeList = json2arr($data['timeList']);
            $hgGoodsList = json2arr($data['hgGoodsList']);
            unset($data['goodsList'], $data['timeList'],$data['hgGoodsList']);
            $flag = [];
            $this->startTrans();
            $data = array_merge($data,$this->getStoreFind(['store_id' => $data['store_id']],'store_id,store_name,terminal_no,store_manager')->toArray());
            $data['start_date'] = strtotime($data['start_date']);
            $data['end_date'] = strtotime($data['end_date']);
            $ah_id = $this->addActivityHg($data);
            if ($ah_id) {
                $flag[] = 1;
                foreach ($goodsList as $gk => $gv) {
                    $gv['a_type'] = 3;
                    $gv['a_id'] = $ah_id;
                    try {
                        validate(VActivity::class)->scene("addGoods")->check($gv);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rValidate($e->getMessage());
                    }
                    $gv = array_merge($gv,$this->getStoreShelvesFind(['ss_id' => $gv['ss_id']],"ss_id,shelves_number,wg_id,goods_id,goods_name,goods_pic,goods_c_id,goods_c_name")->toArray());
                    $flag[] = $this->addActivityGoods($gv);
                }
                foreach ($timeList as $tk => $tv) {
                    $tv['a_type'] = 3;
                    $tv['a_id'] = $ah_id;
                    try {
                        validate(VActivity::class)->scene("addTime")->check($tv);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rValidate($e->getMessage());
                    }
                    $tv['start_time'] = HourMinuteSec2int($tv['start_time']);
                    $tv['end_time'] = HourMinuteSec2int($tv['end_time']);
                    $flag[] = $this->addActivityTime($tv);
                }
                foreach ($hgGoodsList as $hk => $hv) {
                    $hv['ah_id'] = $ah_id;
                    try {
                        validate(VActivity::class)->scene("addHgGoods")->check($hv);
                    } catch (\Exception $e) {
                        $this->rollbackTrans();
                        return $this->rValidate($e->getMessage());
                    }
                    $goods = $this->getGoodsFind(['goods_id' => $hv['goods_id']],'goods_name,pic goods_pic,gc_id  goods_c_id, gc_name goods_c_name');
                    if (!$goods) {
                        $this->rollbackTrans();
                        return $this->rValidate("查无商品信息");
                    }
                    $hv = array_merge($hv,$goods);
                    $flag[] = $this->addActivityHgGoods($hv);
                }
                $result = flag_check($flag);
                $result ? $this->commitTrans() : $this->rollbackTrans();
                return $this->rA($result);
            }
            $this->rollbackTrans();
            return $this->rFail("添加失败");
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 删除加价换购活动
     * @param $ad_id
     * @return bool|string
     */
    public function delInfo($ah_id)
    {
        $this->startTrans();
        $flag[] = $this->delActivityHg(["ah_id" => $ah_id]);
        $where['a_type'] = 3;
        $where['a_id'] = $ah_id;
        $flag[] = $this->delActivityTime($where);
        $flag[] = $this->delActivityGoods($where);
        $flag[] = $this->delActivityHgGoods(['ah_id' => $ah_id]);
        $result = flag_check($flag);
        return $this->checkTrans($result);
    }

}