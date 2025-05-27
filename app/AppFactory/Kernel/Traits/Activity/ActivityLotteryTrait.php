<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/19
 * Time: 16:24
 */

namespace app\AppFactory\Kernel\Traits\Activity;


use app\AppFactory\Kernel\Model\Activity\Lottery\ActivityLotteryModel;

trait ActivityLotteryTrait
{
    public function getActivityLotteryFind($where,$field = "*",$order = "al_id desc")
    {
        return ActivityLotteryModel::getFind($where,$field,$order);
    }

    public function getActivityLotteryList($where,$pageNum = 0,$field = "*", $order = "al_id desc")
    {
        return ActivityLotteryModel::getList($where,$pageNum,$field,$order);
    }

    public function getActivityLotteryListByMachine($where,$field = "*", $order = "al_id desc")
    {
        return ActivityLotteryModel::getListByMachine($where,$field,$order);
    }

    public function getActivityLotteryByMachine()
    {
        $where = 'm_id = ' . $this->machine['m_id'] . " AND status < 3 AND start_time < " . time();
        $alList = $this->getActivityLotteryListByMachine($where, 'al_id,lottery_name,start_time,end_time,price,desc,status');
        if ($alList) {
            foreach ($alList as $key => $al) {
                $update = [];
                $al['content'] = $this->getActivityLotteryContentList(['al_id' => $al['al_id']], 0, 'c_id,content_name,retain_num,probability,g_id,g_name,sku');
                $al['config'] = $this->getActivityLotteryConfigList(['al_id' => $al['al_id']], 0, 'alc_id,active_num,active_type,gifts_num,designated_gift,button_pic');
//                $al['machineList'] = $this->getActivityMachineList(['a_id' => $al['al_id'], 'a_type' => 3], 0, 'm_id,machine_id,machine_name');
                if ($al['status'] == 1) {
                    $update['status'] = 2;
                    $al['status'] = 2;
                }
                if ($al['end_time'] > 0 && $al['end_time'] < time() && $al['status'] != 3) {
                    $update['status'] = 3;
                    $al['status'] = 3;
                }
                if ($update) $this->updateActivityLottery($update,['al_id' => $al['al_id']]);
                $alList[$key] = $al;
            }
        }
        return $alList;
    }

    public function addActivityLottery($insert)
    {
        $insert['creator'] = ($this->manager['manager_id'] ?? 0);
        $al = ActivityLotteryModel::create($insert);
        return $al->al_id;
    }

    public function updateActivityLottery($update,$where = [],$field = [])
    {
        $update['update_id'] = ($this->manager['manager_id'] ?? 0);
        return ActivityLotteryModel::update($update,$where,$field);
    }

    public function delActivityLottery($where)
    {
        return ActivityLotteryModel::whereDel($where);
    }

}