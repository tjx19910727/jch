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
        return AuthManagerRoleModel::whereDel($where);
    }

    /**
     * 获取账号权限节点
     * @param array $manager 账号数据
     * @param string $fatherNodeUrl 父节点URL
     * @return mixed
     */
    public function getManagerNodeList($manager,$fatherNodeUrl = "machine")
    {
        $roleIds = $this->getAuthManagerRoleColumn(['manager_id' => $manager['manager_id']],'role_id');
        // 查询权限根节点
        $machineNodeId = $this->getAuthNodeValue(['url' => $fatherNodeUrl],'node_id');
        if (!$machineNodeId) return $this->r(100,$this->lang("VLogin.permission_denied"));
        // 查询权限所有子节点
        $nodeTree = $this->getAuthNodeChildIdList($machineNodeId);
        $nodeTree[] = $machineNodeId;
        // 非超管
        if ($manager['pid'] > 0) {
            // 查询权限绑定账号角色的所有节点
            $whereRn[] = ['an.node_id', "in", $nodeTree];
            $whereRn['an.status'] = 1;
            $whereRn[] = ['rn.role_id', 'in', $roleIds];
            $nodeList = $this->getAuthRoleNodeList($whereRn, 0, 'an.node_id,an.pid,an.name,an.desc,an.url','an.node_id asc','an.node_id');
        } else {
            $nodeList = $this->getAuthNodeList([['node_id','in',$nodeTree]],0,'node_id,pid,name,desc,url','node_id asc',"node_id");
        }
        return $nodeList;
    }
}