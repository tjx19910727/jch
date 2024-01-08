<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/7
 * Time: 14:57
 */

namespace app\AppFactory\Management\Advertisement;


use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementPushTrait;
use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementResourceTrait;
use app\AppFactory\Kernel\Traits\Store\StoreTrait;
use app\AppFactory\Management\ManagementClient;

class AdvertisementPushClient extends ManagementClient
{
    use AdvertisementPushTrait;
    use AdvertisementResourceTrait;
    use StoreTrait;

    /**
     * 批量推送广告
     * @param $data
     * @return array|string
     */
    public function addMorePush($data)
    {
        $store_id = explode(",",$data['store_id']);
        $res_id = explode(",",$data['res_id']);
        $time = explode(",",$data['time_list']);
        unset($data['store_id'],$data['res_id'],$data['time_list']);
        $data['start_date'] = strtotime($data['start_date']);
        if ($data['start_date'] < time()) $data['status'] = 2;
        $data['end_date'] = strtotime($data['end_date']);
        $flag = [];
        $this->startTrans();
        foreach ($store_id as $value) {
            $store = $this->getStoreFind(['store_id' => $value]);
            foreach ($res_id as $v) {
                $insert = $data;
                $insert['remain_times'] = $data['total_times'];
                $insert['store_id'] = $value;
                $insert['store_name'] = $store['store_name'];
                $insert['terminal_no'] = $store['terminal_no'];
                $insert['res_id'] = $v;
                $res = $this->getAdvertisementResourceFind(['res_id' => $v],'title,file_path,status');
                $res = obj2arr($res);
                if (!$res) {
                    $this->rollbackTrans();
                    return returnState(100,'查无素材');
                }
                if ($res['status'] == 2) {
                    $this->rollbackTrans();
                    return returnState(100,'素材不可用');
                }
                $insert['res_title'] = $res['title'];
                $insert['file_path'] = $res['file_path'];
                foreach ($time as $tk) {
                    $timeList = explode("~",$tk);
                    $insert['start_time'] = HourMinuteSec2int($timeList[0]);
                    $insert['end_time'] = HourMinuteSec2int($timeList[1]);
                    $flag[] = $this->addAdvertisementPush($insert);
                }
            }
        }
        $result = flag_check($flag);
        $result ? $this->commitTrans() : $this->rollbackTrans();
        return $this->rAction($result);
    }

    public function updatePush($data)
    {
        $data['start_date'] = strtotime($data['start_date']);
        if ($data['start_date'] < time()) $data['status'] = 2;
        $data['end_date'] = strtotime($data['end_date']);
        $data['start_time'] = HourMinuteSec2int($data['start_time']);
        $data['end_time'] = HourMinuteSec2int($data['start_time']);
        return $this->rAction($this->updateAdvertisementPush($data,[],["duration_time","total_times","start_date","end_date","start_time","end_time","screen","screen_full"]));
    }

    /**
     * 上架下架广告
     * @param $data
     * @return array|string
     */
    public function upDown($data)
    {
        $adv = $this->getAdvertisementPushFind(['adv_id' => $data['adv_id']]);
        if ($data['status'] == 2) {
            if ($adv['status'] == 5) return $this->r(100,'当前广告素材已被删除');
        }
        $update['adv_id'] = $data['adv_id'];
        $update['status'] = $data['status'];
        $result = $this->updateAdvertisementPush($update);
        return $this->rD($result);
    }
}