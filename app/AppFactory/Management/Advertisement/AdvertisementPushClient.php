<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/7
 * Time: 14:57
 */

namespace app\AppFactory\Management\Advertisement;


use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementPushTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Resource\ResourceTrait;
use app\AppFactory\Management\ManagementClient;

class AdvertisementPushClient extends ManagementClient
{
    use AdvertisementPushTrait;
    use ResourceTrait;
    use MachineTrait;

    /**
     * 批量推送广告
     * @param $data
     * @return array|string
     */
    public function addMorePush($data)
    {
        $m_ids = explode(",", $data['m_id']);
        $res_id = $data['res_id'];
        $time = explode(",", $data['time_list']);
        unset($data['store_id'], $data['res_id'], $data['time_list']);
        $data['start_date'] = strtotime($data['start_date']);
        if ($data['start_date'] < time()) $data['status'] = 2;
        $data['end_date'] = strtotime($data['end_date']);
        $flag = [];
        $this->startTrans();
        foreach ($m_ids as $value) {
            $machine = $this->getMachineFind(['m_id' => $value], 'm_id,machine_name,machine_id');
            if (!$machine) {
                $this->rollbackTrans();
                return $this->rFail($this->lang("VResource.query_machine_no_data"));
            }
            $insert = $data;
            $insert['remain_times'] = $data['total_times'];
            $insert['m_id'] = $value;
            $insert['machine_name'] = $machine['machine_name'];
            $insert['machine_id'] = $machine['machine_id'];
            $insert['res_id'] = $res_id;
            $res = $this->getResourceFind(['res_id' => $res_id], 'title,file_path,status');
            $res = obj2arr($res);
            if (!$res) {
                $this->rollbackTrans();
                return $this->rFail($this->lang('VResource.query_no_data'));
            }
            if ($res['status'] == 2) {
                $this->rollbackTrans();
                return $this->rFail($this->lang('VResource.can_not_use'));
            }
            $insert['res_title'] = $res['title'];
            $insert['file_path'] = $res['file_path'];
            foreach ($time as $tk) {
                $timeList = explode("~", $tk);
                $insert['start_time'] = HourMinuteSec2int($timeList[0]);
                $insert['end_time'] = HourMinuteSec2int($timeList[1]);
                $flag[] = $this->addAdvertisementPush($insert);
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
        return $this->rAction($this->updateAdvertisementPush($data, [], ["duration_time", "total_times", "start_date", "end_date", "start_time", "end_time", "screen", "screen_full"]));
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
            if ($adv['status'] == 5) return $this->r(100, '当前广告素材已被删除');
        }
        $update['adv_id'] = $data['adv_id'];
        $update['status'] = $data['status'];
        $result = $this->updateAdvertisementPush($update);
        return $this->rD($result);
    }
}