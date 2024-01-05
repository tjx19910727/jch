<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:23
 */

namespace app\AppFactory\Kernel\Traits\Auth;


use app\AppFactory\Kernel\Model\Auth\AuthManagerRoleModel;

trait AuthManagerRoleTrait
{

    public function getAuthManagerRoleFind($where,$field = "*", $order = "")
    {
        $where['is_del'] = 2;
        return AuthManagerRoleModel::getFind($where,$field,$order);
    }

    public function getAuthManagerRoleColumn($where,$column)
    {
        return AuthManagerRoleModel::getColumn($where,$column);
    }

    public function getAuthManagerRoleList($where,$pageNum = 0,$field = "*",$order = "")
    {
        $where['is_del'] = 2;
        return AuthManagerRoleModel::getJoinRoleList($where,$pageNum,$field,$order);
    }

    public function addAuthManagerRole($insert)
    {
        $insert['creator'] = $this->manager['manager_id'];
        $role_id = explode(",",$insert['role_id']);
        foreach ($role_id as $key => $value) {
            $insert['role_id'] = $value;
            $data = AuthManagerRoleModel::create($insert);
            $return[] = $data->mr_id;
        }
        return $return;
    }

    public function updateAuthManagerRole($update,$where = [],$field = [])
    {
        $update['update_id'] = $this->manager['manager_id'];
        return AuthManagerRoleModel::update($update,$where,$field);
    }

    public function delAuthManagerRole($where)
    {
        return AuthManagerRoleModel::destroy($where);
    }

}