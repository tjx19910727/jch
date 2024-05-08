<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/10/7
 * Time: 14:57
 */

namespace app\AppFactory\Management\Advertisement;


use app\AppFactory\AppFactory;
use app\AppFactory\Kernel\Traits\Advertisement\AdvertisementPushTrait;
use app\AppFactory\Kernel\Traits\Machine\MachineTrait;
use app\AppFactory\Kernel\Traits\Resource\ResourceTrait;
use app\AppFactory\Management\ManagementClient;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class AdvertisementPushClient extends ManagementClient
{
    use AdvertisementPushTrait;
    use ResourceTrait;
    use MachineTrait;

    public function getGroupList($where,$pageNum = 0,$field = "*", $group = "", $order = "")
    {
        $data = $this->getAdvertisementPushGroupList($where,$pageNum,$field,$group,$order);
        return $this->rQ($data);
    }

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
        $batch_num = date("YmdHis") . uniqid();
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
            $res = $this->getResourceFind(['res_id' => $res_id], 'title,file_path,status,type');
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
            $insert['type'] = $res['type'];
            foreach ($time as $tk) {
                $timeList = explode("~", $tk);
                $insert['batch_num'] = $batch_num;
                $insert['start_time'] = HourMinuteSec2int($timeList[0]);
                $insert['end_time'] = HourMinuteSec2int($timeList[1]);
                $insert['ao_id'] = $this->manager['ao_id'];
                $flag[] = $this->addAdvertisementPush($insert);
            }
        }
        $result = flag_check($flag);
        if ($result) {
            $this->commitTrans();
            return $this->rSuccess();
        }
        $this->rollbackTrans();
        return $this->rFail();
    }

    /**
     * 修改推送广告信息
     * @param $data
     * @return array|string
     */
    public function updatePush($data)
    {
        $data['start_date'] = strtotime($data['start_date']);
        if ($data['start_date'] < time()) $data['status'] = 2;
        $data['end_date'] = strtotime($data['end_date']);
        $data['start_time'] = HourMinuteSec2int($data['start_time']);
        $data['end_time'] = HourMinuteSec2int($data['end_time']);
        $result = $this->updateAdvertisementPush($data, [], ["duration_time", "total_times", "start_date", "end_date", "start_time", "end_time", "screen", "screen_full"]);
        if ($result) {
            // 触发下发终端广告更新
            return $this->rSuccess();
        }
        return $this->rFail();
    }

    /**
     * 上架下架广告
     * @param $data
     * @return array|bool|string
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function upDown($data)
    {
        $where = [];
        if (isset($data['adv_id'])) $where = [['adv_id' ,"in", $data['adv_id']]];
        if (isset($data['m_id'])) $where = [['m_id',"in",$data['m_id']]];
        if (isset($data['batch_num'])) $where['batch_num'] = $data['batch_num'];
        if (!$where) return $this->r(100,$this->lang("VAdvertisement.upDown_where_empty"));
        $adv = $this->getAdvertisementPushList($where,0,'adv_id,adv_title,status');
        if ($adv) {
            $advIds = [];
            $this->startTrans();
            $flag = [];
            foreach ($adv as $k => $v) {
                if ($data['status'] == 2) {
                    if ($v['status'] == 5) {
                        $this->rollbackTrans();
                        return $this->r(100, "【" . $adv['adv_title'] ."】：" . $this->lang("VAdvertisement.resource_is_del"));
                    }
                }
                $update['adv_id'] = $v['adv_id'];
                $update['status'] = $data['status'];
                $flag[] = $this->updateAdvertisementPush($update);
                $advIds[] = $v['adv_id'];
            }
            $result = $this->checkFlag($flag);
            if ($result) {
                $this->commitTrans();
                return $this->r(200,$this->lang("action_success"),$flag);
            }
            $this->rollbackTrans();
        }
        return $this->r(100,$this->lang("action_fail"));
    }

    /**
     * 触发广告更新
     * @param $where
     * @return array|string
     */
    public function triggerUpdate($where)
    {
        try {
            $flag = [];
            $adv = $this->getAdvertisementPushList($where);
            foreach ($adv as $key => $value) {
//                if ($value['remain_times'] <= 0) return $this->rFail($this->lang("VAdvertisement.remain_times_empty"));
                if ($value['remain_times'] > 0 && $value['status'] < 3 && $value['start_date'] <= strtotime(date("Y-m-d"))) {
                    if (!$value['end_date'] || ($value['end_date'] > 0 && $value['end_date'] >= strtotime(date("Y-m-d")))) {
                        $config = [
                            "machine_id" => $value['machine_id'],
                            "key" => env("api.md5Key"),
                        ];
                        $app = AppFactory::machine($config);
                        $flag[] = $app->sendMq->triggerUpdateAD();
                    }
                }
            }
            return $this->r(200, $this->lang("action_success"), $flag);
        } catch (DataNotFoundException $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        } catch (ModelNotFoundException $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        } catch (DbException $e) {
            actionException($e,1);
            return $this->rValidate($e->getMessage());
        }
    }
}
