<?php
/**
 * 限时活动
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/7
 * Time: 11:31
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Traits\Activity\ActivityDiscountTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityTimeTrait;
use app\AppFactory\Kernel\Traits\Store\StoreShelvesTrait;
use app\AppFactory\Kernel\Traits\Store\StoreTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\VActivity;

class ActivityDiscountClient extends ManagementClient
{
    use ActivityDiscountTrait;
    use ActivityGoodsTrait;
    use ActivityTimeTrait;
    use StoreShelvesTrait;
    use StoreTrait;

    /**
     * 获取详情
     * @param $where
     * @param string $field
     * @param string $order
     * @return array|string
     */
    public function getDetails($where,$field = "*",$order = "")
    {
        try {
            $data = $this->getActivityDiscountFind($where, $field, $order);
            if ($data) {
                $data['timeList'] = $this->getActivityTimeList(['a_id' => $data->ad_id, 'a_type' => 1], 0, 'at_id,start_time,end_time', 'start_time asc');
                $data['goodsList'] = $this->getActivityGoodsList(['a_id' => $data->ad_id, 'a_type' => 1], 0, 'ag_id,store_id,ss_id,shelves_number,wg_id,goods_id,goods_name,goods_pic,goods_c_id,goods_c_name', 'ag_id desc');
            }
            return $this->rQ($data);
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 添加限时活动
     * @param $data
     * @return array|string
     */
    public function addInfo($data)
    {
        try {
            $goodsList = json2arr($data['goodsList']);
            $timeList = json2arr($data['timeList']);
            unset($data['goodsList'], $data['timeList']);
            $flag = [];
            $this->startTrans();
            $data = array_merge($data,$this->getStoreFind(['store_id' => $data['store_id']],'store_id,store_name,terminal_no,store_manager')->toArray());
            $data['start_date'] = strtotime($data['start_date']);
            $data['end_date'] = strtotime($data['end_date']);
            $ad_id = $this->addActivityDiscount($data);
            if ($ad_id) {
                $flag[] = 1;
                foreach ($goodsList as $gk => $gv) {
                    $gv['a_type'] = 1;
                    $gv['a_id'] = $ad_id;
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
                    $tv['a_type'] = 1;
                    $tv['a_id'] = $ad_id;
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
     * 删除限时活动
     * @param $ad_id
     * @return bool|string
     */
    public function delInfo($ad_id)
    {
        $this->startTrans();
        $flag[] = $this->delActivityDiscount(["ad_id" => $ad_id]);
        $where['a_type'] = 1;
        $where['a_id'] = $ad_id;
        $flag[] = $this->delActivityTime($where);
        $flag[] = $this->delActivityGoods($where);
        $result = flag_check($flag);
        return $this->checkTrans($result);
    }
}