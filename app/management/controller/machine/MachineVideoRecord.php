<?php

namespace app\management\controller\machine;

use app\management\controller\Common;
use app\management\validate\Machine\VMachineVideoRecord;

class MachineVideoRecord extends Common
{
    /**
     * 分页查询设备视频录制记录。
     * @return array|\think\response\Json
     */
    public function getList()
    {
        return $this->app->machineVideoRecord->getRecordList(input());
    }

    /**
     * 下发设备视频录制指令。
     * @return array|\think\response\Json
     */
    public function recordVideo()
    {
        $postData = input();
        try {
            validate(VMachineVideoRecord::class)->scene('recordVideo')->check($postData);
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineVideoRecord->recordVideo($postData);
    }

    /**
     * 录制成功后通知设备上传对应视频。
     * @return array|\think\response\Json
     */
    public function getVideo()
    {
        $postData = input();
        try {
            validate(VMachineVideoRecord::class)->scene('getVideo')->check($postData);
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineVideoRecord->getVideo($postData);
    }
}
