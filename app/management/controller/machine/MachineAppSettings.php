<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/6/3
 * Time: 14:00
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class MachineAppSettings extends Common
{
    /**
     * 设备端语音配置列表(type=1)
     * @return array|\think\response\Json
     */
    public function getList()
    {
        $postData = input();
        return $this->app->machineAppSettings->getSettingsList($postData);
    }

    /**
     * 查询单条配置
     * @return array|\think\response\Json
     */
    public function getFind()
    {
        $postData = input();
        return $this->app->machineAppSettings->getSettingsFind($postData);
    }

    /**
     * 修改配置
     * 1) 单个编辑：传id或key + value
     * 2) 批量编辑：传data数组
     * @return array|\think\response\Json
     */
    public function update()
    {
        $postData = input();
        return $this->app->machineAppSettings->updateSettings($postData);
    }
}
