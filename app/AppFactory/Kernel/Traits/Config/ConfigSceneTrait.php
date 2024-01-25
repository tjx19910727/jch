<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 11:51
 */

namespace app\AppFactory\Kernel\Traits\Config;


use app\AppFactory\Kernel\Model\Config\ConfigSceneModel;

trait ConfigSceneTrait
{
    public function getConfigSceneFind($where,$field = "*",$order = "")
    {
        return ConfigSceneModel::getFind($where,$field,$order);
    }

    public function getConfigSceneList($where,$pageNum = 0, $field = "*",$order = "")
    {
        return ConfigSceneModel::getList($where,$pageNum,$field,$order);
    }

    public function addConfigScene($insert)
    {
        !isset($this->manager['manager_id']) ? :$insert['creator'] = $this->manager['manager_id'];
        $scene = ConfigSceneModel::create($insert);
        return $scene->id;
    }

    public function updateConfigScene($update, $where = [], $field = [])
    {
        return ConfigSceneModel::update($update,$where,$field);
    }

    public function delConfigScene($where)
    {
        return ConfigSceneModel::whereDel($where);
    }
}