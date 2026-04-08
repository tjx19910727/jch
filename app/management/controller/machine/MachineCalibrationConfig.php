<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/3/31
 * Time: 11:00
 */

namespace app\management\controller\machine;


use app\management\controller\Common;
use app\management\validate\Machine\VMachineCalibrationConfig;

class MachineCalibrationConfig extends Common
{
    protected $validatePath = VMachineCalibrationConfig::class . ".";

    /**
     * 列表查询
     * 返回格式：[{"title":"Y轴","key":"y_z","value":"300"}]
     * @return array|\think\response\Json
     */
    public function getList()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'getList');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineCalibrationConfig->getCalibrationList($postData);
    }

    /**
     * 查询一条配置
     * @return array|\think\response\Json
     */
    public function getFind()
    {
        $postData = input();
        return $this->app->machineCalibrationConfig->getCalibrationFind($postData);
    }

    /**
     * 新增一条配置
     * @return array|\think\response\Json
     */
    public function add()
    {
        $postData = input();
        try {
            $this->validate($postData, $this->validatePath . 'add');
        } catch (\Exception $e) {
            return returnValidate($e->getMessage());
        }
        return $this->app->machineCalibrationConfig->addCalibration($postData);
    }

    /**
     * 修改配置
     * 1) 支持按id修改单条
     * 2) 支持按data数组全量提交
     * @return array|\think\response\Json
     */
    public function update()
    {
        $postData = input();
        if (isset($postData['data']) && is_array($postData['data'])) {
            try {
                $this->validate($postData, $this->validatePath . 'updateList');
            } catch (\Exception $e) {
                return returnValidate($e->getMessage());
            }
            return $this->app->machineCalibrationConfig->updateCalibrationList($postData);
        }
        return $this->app->machineCalibrationConfig->updateCalibration($postData);
    }

    /**
     * 删除配置
     * 支持按id、按key、按keys数组删除
     * @return array|\think\response\Json
     */
    public function del()
    {
        $postData = input();
        return $this->app->machineCalibrationConfig->delCalibration($postData);
    }
}
