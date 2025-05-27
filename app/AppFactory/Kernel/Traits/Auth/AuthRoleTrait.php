<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:23
 */

namespace app\AppFactory\Kernel\Traits\Auth;


use app\AppFactory\Kernel\Model\Auth\AuthRoleModel;

trait AuthRoleTrait
{

    public function getAuthRoleFind($where,$field = "*",$order = "")
    {
        return AuthRoleModel::getFind($where,$field,$order);
    }

    public function getAuthRoleList($where,$pageNum = 0,$field = "*",$order = "")
    {
        return AuthRoleModel::getList($where,$pageNum,$field,$order);
    }

    public function addAuthRole($insert)
    {
        $insert['creator'] = $this->manager['manager_id'];
        $data = AuthRoleModel::create($insert);
        return $data->role_id;
    }

    public function updateAuthRole($update,$where = [],$field = [])
    {
        $update['update_id'] = $this->manager['manager_id'];
        return AuthRoleModel::update($update,$where,$field);
    }

    public function delAuthRole($where)
    {
        return AuthRoleModel::whereDel($where);
    }
}