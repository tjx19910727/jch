<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/8
 * Time: 11:22
 */

namespace app\AppFactory\Management\Auth;


use app\AppFactory\Kernel\Traits\Auth\AuthOrganizationRoleTrait;
use app\AppFactory\Management\ManagementClient;

class AuthOrganizationRoleClient extends ManagementClient
{
    use AuthOrganizationRoleTrait;


    /**
     * 绑定与取消绑定角色权限
     * @param $data
     * @return bool|string
     * @throws \Exception
     */
    public function bind($data)
    {
        $flag = [];
        $roleId = explode(",", $data['roleList']);

        $this->startTrans();
        // 查询已存在的关联权限角色ID
        $where['ao_id'] = $data['ao_id'];
        $authRoleNode = $this->getAuthOrganizationRoleColumn($where,'role_id');
        $authRoleNode = obj2arr($authRoleNode);
        // 存在新增，在数组1中，不在数组2中
        $add = array_diff($roleId,$authRoleNode);
        // 存在删除，在数组1中，不在数组2中
        $del = array_diff($authRoleNode,$roleId);
        if ($add) {
            $insert = [
                "ao_id" => $data['ao_id'],
                "creator" => $this->manager['manager_id'],
            ];
            foreach ($add as $value) {
                $insertOr = $insert;
                $insertOr['role_id'] = $value;
                $flag[] = $this->addAuthOrganizationRole($insertOr);
            }
        }
        if ($del) {
            $flag[] = $this->updateAuthOrganizationRole(['is_del' => 1],['ao_id' => $data['ao_id'],['role_id','in',$del],'is_del' => 2],['is_del','update_id','update_time']);
        }
        $result = flag_check($flag);
        return $this->checkTrans($result);
    }
}