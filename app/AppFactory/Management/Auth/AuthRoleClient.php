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
use think\facade\Db;

class AuthRoleClient extends ManagementClient
{
    use AuthRoleTrait,AuthOrganizationRoleTrait;
    use AuthRoleNodeTrait;

    public function add($postData, $rA = 1)
    {
        $this->startTrans();
        try {
            unset($postData['template_id']);
            $roleId = $this->addAuthRole($postData);
            $this->commitTrans();
            return $rA ? $this->rA($roleId) : $roleId;
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function update($postData, $where = [], $field = [], $rU = 1)
    {
        $this->startTrans();
        try {
            unset($postData['template_id']);
            $result = $this->updateAuthRole($postData, $where, $field);
            $this->commitTrans();
            return $rU ? $this->rU($result) : $result;
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

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

    /**
     * 获取角色关联的账户列表（不分页）
     * @param int $roleId
     * @return array|\think\response\Json
     */
    public function getManagers($roleId)
    {
        $list = Db::name('auth_manager_role')
            ->alias('mr')
            ->join('auth_manager au', 'au.manager_id = mr.manager_id')
            ->where([
                'mr.role_id' => intval($roleId),
                'mr.is_del' => 2,
                'au.status' => 1,
            ])
            ->field('au.manager_id,au.nickname,au.account,au.real_name,au.status')
            ->distinct(true)
            ->order('au.manager_id asc')
            ->select();

        return $this->rQ($list);
    }

    
    /**
     * 获取角色列表，并统计每个角色关联的有效账户数
     * @param array $where
     * @param int $pageNum
     * @param string $field
     * @param string $order
     * @param int $rQ
     * @return mixed
     */
    public function getList($where = [], $pageNum = 0, $field = "*", $order = "", $rQ = 1)
    {
        $field .= ",(SELECT COUNT(DISTINCT amr.manager_id)
            FROM auth_manager_role amr
            INNER JOIN auth_manager au ON au.manager_id = amr.manager_id
            WHERE amr.role_id = a.role_id
            AND amr.is_del = 2
            AND au.status = 1) manager_num";
        $data = $this->getAuthRoleList($where, $pageNum, $field, $order);
        return $rQ ? $this->rQ($data) : $data;
    }
}
