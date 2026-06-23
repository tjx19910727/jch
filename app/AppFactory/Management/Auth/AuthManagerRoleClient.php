<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:15
 */

namespace app\AppFactory\Management\Auth;


use app\AppFactory\Kernel\Traits\Auth\AuthManagerRoleTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthNodeTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthOrganizationRoleTrait;
use app\AppFactory\Kernel\Traits\Auth\AuthRoleNodeTrait;
use app\AppFactory\Management\ManagementClient;
use think\facade\Db;
use think\response\Json;

class AuthManagerRoleClient extends ManagementClient
{
    use AuthManagerRoleTrait;
    use AuthRoleNodeTrait;
    use AuthNodeTrait;
    use AuthOrganizationRoleTrait;

    public function add($postData, $rA = 1)
    {
        $this->startTrans();
        try {
            $result = $this->addAuthManagerRole($postData);
            if (!$result) {
                $this->rollbackTrans();
                return $this->rFail("账号绑定角色失败");
            }
            $this->commitTrans();
            return $rA ? $this->rA($result) : $result;
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    public function setRoleManagers($postData)
    {
        $roleId = intval($postData['role_id'] ?? 0);
        $managerIds = $this->normalizeManagerIds($postData['manager_ids'] ?? []);
        $role = Db::name('auth_role')->where('role_id', $roleId)->find();
        if (!$role) return $this->rFail("权限角色不存在");
        if (intval($this->manager['ao_id']) > 1 && intval($role['ao_id']) !== intval($this->manager['ao_id'])) {
            return $this->rFail("无权操作其他组织的权限角色");
        }
        if ($managerIds) {
            $validManagerIds = Db::name('auth_manager')
                ->where('manager_id', 'in', $managerIds)
                ->where('ao_id', intval($role['ao_id']))
                ->column('manager_id');
            if (count($validManagerIds) !== count($managerIds)) {
                return $this->rFail("账号不存在或与角色所属组织不一致");
            }
        }

        $this->startTrans();
        try {
            $activeIds = Db::name('auth_manager_role')
                ->where(['role_id' => $roleId, 'is_del' => 2])
                ->column('manager_id');
            $removeIds = array_diff(array_map('intval', $activeIds), $managerIds);
            $addIds = array_diff($managerIds, array_map('intval', $activeIds));
            if ($removeIds) {
                Db::name('auth_manager_role')
                    ->where(['role_id' => $roleId, 'is_del' => 2])
                    ->where('manager_id', 'in', $removeIds)
                    ->update(['is_del' => 1, 'update_id' => $this->manager['manager_id'], 'update_time' => time()]);
            }
            foreach ($addIds as $managerId) {
                $exists = Db::name('auth_manager_role')
                    ->where(['role_id' => $roleId, 'manager_id' => $managerId])
                    ->order('mr_id desc')
                    ->find();
                if ($exists) {
                    Db::name('auth_manager_role')->where('mr_id', $exists['mr_id'])->update([
                        'is_del' => 2, 'update_id' => $this->manager['manager_id'], 'update_time' => time(),
                    ]);
                } else {
                    Db::name('auth_manager_role')->insert([
                        'role_id' => $roleId, 'manager_id' => $managerId, 'is_del' => 2,
                        'creator' => $this->manager['manager_id'], 'create_time' => time(),
                    ]);
                }
            }
            return $this->checkTrans(true);
        } catch (\Exception $e) {
            $this->rollbackTrans();
            actionException($e, 1);
            return $this->rTryCatch($e->getMessage());
        }
    }

    protected function normalizeManagerIds($managerIds)
    {
        if (is_string($managerIds)) {
            $decoded = json_decode($managerIds, true);
            $managerIds = is_array($decoded) ? $decoded : explode(',', $managerIds);
        }
        if (!is_array($managerIds)) return [];
        return array_values(array_unique(array_filter(array_map('intval', $managerIds))));
    }

    protected $commonNode = [
        "/management/common/getSelfRoleNode",
        "/management/common/getMineInfo",
        "/management/common/checkPwd",
        "/management/config.config/getFind",
        "/management/config.config_lang/getList",
    ];

    /**
     * 校验账号权限节点
     * @return array|bool|string|Json
     */
    public function checkAuthNode()
    {
        $url = request()->baseUrl();
        // 查节点数据
        $where['url'] = $url;
        $field = "node_id,pid,name,icon,url,desc,sort,type,is_auth,data_auth,status";
        $authNode = $this->getAuthNodeFind($where,$field);
        $authNode = obj2arr($authNode);
        if (in_array($url,$this->commonNode)) return $authNode ?? [];
        // 超管全权限免验证
        if ($this->manager['pid'] === 0) return $authNode;
        if (!$authNode) return $this->r(100,"该功能尚未开放");

        $authNode['d_type'] = 0;
        $authNode['data_scope'] = '';
        if ($authNode['status'] == 2) return $this->rFail("该功能已被禁用");
        // 不需要绝对校验
        if ($authNode['is_auth'] == 2) return $authNode;

        $role = $this->getAuthManagerRoleColumn(['manager_id' => $this->manager['manager_id'],'is_del' => 2],'role_id');
        // 查询组织绑定的权限角色
        $or = $this->getAuthOrganizationRoleColumn(['ao_id' => $this->manager['ao_id'],'is_del' => 2],'role_id');
        if (!$role) $role = [];
        if (!$or) $or = [];
        $role = array_unique(array_merge($role,$or));
        if (!$role) return $this->rFail("您暂未被授予权限角色，无法使用系统");


        $roleNodes = $this->resolveManagerRoleNodes($this->manager, $role, intval($authNode['node_id']));
        if (!$roleNodes) return $this->r(100,"您无权限操作【" . $authNode['name']. "】");
        $authNode['d_type'] = $roleNodes[0]['d_type'];
        $authNode['data_scope'] = $roleNodes[0]['data_scope'] ?? '';
        return $authNode;
    }


}
