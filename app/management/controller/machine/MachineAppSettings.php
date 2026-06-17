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

    /**
     * 新增配置（支持多设备，m_id格式: 127,188,199）
     * @return array|\think\response\Json
     */
    public function add()
    {
        $postData = input();
        return $this->app->machineAppSettings->addSettings($postData);
    }

    /**
     * 新增表单：返回空数据的默认字段数组
     * @return array|\think\response\Json
     */
    public function getAddForm()
    {
        return $this->app->machineAppSettings->getAddForm();
    }

    /**
     * 讯飞文字转语音
     * 传入国家编码和文字，调用讯飞TTS接口返回语音文件URL
     * @return array|\think\response\Json
     */
    public function textToSpeech()
    {
        $postData = input();
        return $this->app->machineAppSettings->textToSpeech($postData);
    }

    
}
