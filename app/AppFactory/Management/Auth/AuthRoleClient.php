<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:15
 */

namespace app\AppFactory\Management\Auth;


use app\AppFactory\Kernel\Traits\Auth\AuthOrganizationRoleTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthRoleNodeTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthRoleTrait;
use app\AppFactory\Management\ManagementClient;

class AuthRoleClient extends ManagementClient
{
    use AuthRoleTrait,AuthOrganizationRoleTrait;
    use AuthRoleNodeTrait;

    /**
     * 复制权限角色
     * @param $postData
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function copy($postData)
    {
        $role = $this->getAuthRoleFind(['role_id' => $postData['role_id']],'sort,status');
        if (!$role) return $this->r(100,$this->lang("query_fail"));
        $role = $role->toArray();
        $newRole = $role;
        $newRole['name'] = $postData['name'];
        $newRole['desc'] = $postData['desc'] ?? null;
        $this->startTrans();
        $newId = $this->addAuthRole($newRole);
        if (!$newId) {
            $this->rollbackTrans();
            return $this->r(100,$this->lang("copy_fail"));
        }
        $roleNodeList = $this->getAuthRoleNodeList(['rn.role_id' => $postData['role_id']],0,"($newId) role_id,rn.node_id,rn.d_type,(" . $this->manager['manager_id'] . ") creator");
        if ($roleNodeList) {
            $roleNodeList = $roleNodeList->toArray();
            if ($roleNodeList) {
                $result = $this->addMoreAuthRoleNode($roleNodeList);
                if (!$result) {
                    $this->rollbackTrans();
                    return $this->r(100, $this->lang("copy_fail"));
                }
            }
        }
        $this->commitTrans();
        return $this->r(200,$this->lang("copy_success"));
    }
}