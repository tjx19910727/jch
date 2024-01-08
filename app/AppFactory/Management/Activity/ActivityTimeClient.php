<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/11/9
 * Time: 14:11
 */

namespace app\AppFactory\Management\Activity;


use app\AppFactory\Kernel\Traits\Activity\ActivityTimeTrait;
use app\AppFactory\Management\ManagementClient;
use app\management\validate\VActivity;

class ActivityTimeClient extends ManagementClient
{
    use ActivityTimeTrait;

    public function addInfo($data)
    {
        $this->startTrans();
        foreach ($data as $tk => $tv) {
            $tv['a_id'] = $data['a_id'];
            $tv['a_type'] = $data['a_type'];
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
        return $this->checkTrans($result);
    }
}