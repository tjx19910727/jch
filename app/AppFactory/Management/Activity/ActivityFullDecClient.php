<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/8
 * Time: 14:15
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Traits\Activity\ActivityFullDecTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityGoodsTrait;
use app\AppFactory\Kernel\Traits\Activity\ActivityTimeTrait;
use app\AppFactory\Kernel\Traits\Store\StoreShelvesTrait;
use app\AppFactory\Kernel\Traits\Store\StoreTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\VActivity;

class ActivityFullDecClient extends ManagementClient
{
    use ActivityFullDecTrait,
        ActivityTimeTrait,
        ActivityGoodsTrait,
        StoreTrait,
        StoreShelvesTrait;

    /**
     * 查询满减活动详情
     * @param $where
     * @param string $field
     * @param string $order
     * @return array|string
     */
    public function getDetails($where,$field = "*", $order = "")
    {
        try {
            $data = $this->getActivityFullDecFind($where, $field, $order);
            if ($data) {
                $data['timeList'] = $this->getActivityTimeList(['a_id' => $data->afd_id, 'a_type' => 2], 0, 'at_id,start_time,end_time', 'start_time asc');
                $data['goodsList'] = $this->getActivityGoodsList(['a_id' => $data->afd_id, 'a_type' => 2], 0, 'ag_id,store_id,ss_id,shelves_number,wg_id,goods_id,goods_name,goods_pic,goods_c_id,goods_c_name', 'ag_id desc');
            }
            return $this->rQ($data);
        } catch (\Exception $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }

    /**
     * 添加满减活动
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
            $afd = $this->addActivityFullDec($data);
            if ($afd) {
                $flag[] = 1;
                foreach ($goodsList as $gk => $gv) {
                    $gv['a_type'] = 2;
                    $gv['a_id'] = $afd;
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
                    $tv['a_type'] = 2;
                    $tv['a_id'] = $afd;
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
     * 删除满减活动
     * @param $ad_id
     * @return bool|string
     */
    public function delInfo($afd_id)
    {
        $this->startTrans();
        $flag[] = $this->delActivityFullDesc(["afd_id" => $afd_id]);
        $where['a_type'] = 2;
        $where['a_id'] = $afd_id;
        $flag[] = $this->delActivityTime($where);
        $flag[] = $this->delActivityGoods($where);
        $result = flag_check($flag);
        return $this->checkTrans($result);
    }
}