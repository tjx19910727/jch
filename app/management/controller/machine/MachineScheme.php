<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/7/1
 * Time: 19:12
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class MachineScheme extends Common
{
    protected $validatePath = '';

    /**
     * 获取推荐上架方案
     */
    public function getRecommendScheme()
    {
        return $this->app->machineScheme->getRecommendScheme();
    }

    /**
     * 获取方案列表
     */
    public function getList()
    {
        return $this->app->machineScheme->getList();
    }

    /**
     * 获取方案详情
     */
    public function getDetail()
    {
        return $this->app->machineScheme->getDetail();
    }

    /**
     * 确认方案
     */
    public function confirmScheme()
    {
        return $this->app->machineScheme->confirmScheme();
    }

    /**
     * 取消方案
     */
    public function cancelScheme()
    {
        return $this->app->machineScheme->cancelScheme();
    }
}