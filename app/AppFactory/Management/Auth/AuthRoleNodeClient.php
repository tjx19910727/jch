<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:16
 */

namespace app\AppFactory\Management\Auth;


use app\AppFactory\Kernel\Traits\Auth\AuthNodeTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthRoleNodeTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthRoleTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;

class AuthRoleNodeClient extends ManagementClient
{
    use AuthRoleNodeTrait;
    use AuthNodeTrait;
    use AuthRoleTrait;

    /**
     * 绑定与取消绑定角色权限
     * @param $data
     * @return bool|string
     * @throws \Exception
     */
    public function bind($data)
    {
        $role = Db::name('auth_role')->where(['role_id' => intval($data['role_id'])])->field('template_id')->find();
        if ($role && intval($role['template_id'] ?? 0) > 0) {
            return $this->rFail("该角色已关联权限模板，请通过角色权限模板维护节点");
        }
        $flag = [];$roleNode = [];
        $data['nodeList'] = json2arr($data['nodeList']);
        $nodeIds = array_keys($data['nodeList']);

        $this->startTrans();
        try {// 已存在的修改d_type
            $where['role_id'] = $data['role_id'];
            $authRoleNode = $this->getAuthRoleNodeList($where, 0, 'rn.rn_id,rn.node_id,rn.d_type');
            $authRoleNode = obj2arr($authRoleNode);
            if ($authRoleNode) {
                foreach ($authRoleNode as $key => $value) {
                    $roleNode[] = $value['node_id'];
                    if (isset($data['nodeList'][$value['node_id']]) && $value['d_type'] != $data['nodeList'][$value['node_id']]) {
                        $flag[] = $this->updateAuthRoleNode(['rn_id' => $value['rn_id'], 'd_type' => $data['nodeList'][$value['node_id']]]);
                    }
                }
            }// 存在新增，在数组1中，不在数组2中
            $add = array_diff($nodeIds, $roleNode);// 存在删除，在数组1中，不在数组2中
            $del = array_diff($roleNode, $nodeIds);
            if ($add) {
                $insert = [
                    "role_id" => $data['role_id'],
                    "creator" => $this->manager['manager_id'],
                ];
                foreach ($add as $value) {
                    $insertRn = $insert;
                    $insertRn['node_id'] = $value;
                    $insertRn['d_type'] = $data['nodeList'][$value] ?? 0;
                    $insertAll[] = $insertRn;
                }
                $flag[] = $this->addMoreAuthRoleNode($insertAll);
            }
            if ($del) {
                $flag[] = $this->updateAuthRoleNode(['is_del' => 1], ['role_id' => $data['role_id'], ['node_id', 'in', $del], 'is_del' => 2], ['is_del', 'update_id', 'update_time']);
            }
            $result = flag_check($flag);
            return $this->checkTrans($result);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e,1);
            return $this->rTryCatch($e->getMessage());
        }
    }
}
