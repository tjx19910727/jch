<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/10
 * Time: 14:16
 */

namespace app\AppFactory\Kernel\Traits\Resource;


use app\AppFactory\Kernel\Model\Resource\ResourceModel;

trait ResourceTrait
{
    public function getResourceList($where,$pageNum = 0,$field = "*",$order = "")
    {
        return ResourceModel::getList($where,$pageNum,$field,$order);
    }

    public function getResourceFind($where,$field = "*",$order = "")
    {
        return ResourceModel::getFind($where,$field,$order);
    }

    public function addResource($insert)
    {
        isset($insert['creator']) ? : $insert['creator'] = ($this->manager['manager_id'] ?? 0);
        !isset($this->manager['ao_id']) ? : $insert['ao_id'] = $this->manager['ao_id'];
        $r = ResourceModel::create($insert);
        return $r->res_id;
    }

    public function updateResource($update,$where = [],$field = [])
    {
        return ResourceModel::update($update,$where,$field);
    }

    public function delResource($where)
    {
        return ResourceModel::whereDel($where);
    }

}