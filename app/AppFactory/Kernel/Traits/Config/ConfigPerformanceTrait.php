<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/16
 * Time: 17:06
 */

namespace app\AppFactory\Kernel\Traits\Config;


use app\AppFactory\Kernel\Model\Config\ConfigPerformanceModel;

trait ConfigPerformanceTrait
{
    public function getConfigPerformanceFind($where,$field = "*",$order = "")
    {
        return ConfigPerformanceModel::getFind($where,$field,$order);
    }

    public function getConfigPerformanceList($where,$pageNum = 0,$field = "*",$order = "create_time desc")
    {
        return ConfigPerformanceModel::getList($where,$pageNum,$field,$order);
    }

    public function addConfigPerformance($insert)
    {
        if (isset($this->manager['manager_id'])) $insert['creator'] = $this->manager['manager_id'];
        $cp = ConfigPerformanceModel::create($insert);
        return $cp->cp_id;
    }

    public function updateConfigPerformance($update,$where = [],$field = [])
    {
        if (isset($this->manager['manager_id'])) $update['update_id'] = $this->manager['manager_id'];
        return ConfigPerformanceModel::update($update,$where,$field);
    }

    public function delConfigPerformance($where)
    {
        return ConfigPerformanceModel::destroy($where);
    }
}