<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2026/7/1
 * Time: 11:45
 */

namespace app\management\controller\machine;


use app\management\controller\Common;

class MachineLayoutModel extends Common
{
    protected $validatePath = '';
    protected $field = "*";

    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData, false, ["model_name" => "like", "model_code" => "like"]);
        return $this->app->machineLayoutModel->getList($where, $pageNum);
    }

    public function save()
    {
        return $this->app->machineLayoutModel->save();
    }

    public function getDetail()
    {
        return $this->app->machineLayoutModel->getDetail();
    }

    public function del()
    {
        return $this->app->machineLayoutModel->del();
    }

    public function recalc()
    {
        return $this->app->machineLayoutModel->recalc();
    }

    /**
     * 获取设备等级关联的布局模板列表
     */
    public function getLevelLayoutRel()
    {
        return $this->app->machineLayoutModel->getLevelLayoutRel();
    }

    /**
     * 保存设备等级与布局模板的关联
     */
    public function saveLevelLayoutRel()
    {
        return $this->app->machineLayoutModel->saveLevelLayoutRel();
    }
}
