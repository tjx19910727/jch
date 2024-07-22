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
use think\response\Json;

class AuthManagerRoleClient extends ManagementClient
{
    use AuthManagerRoleTrait;
    use AuthRoleNodeTrait;
    use AuthNodeTrait;
    use AuthOrganizationRoleTrait;

    protected $commonNode = [
        "/management/common/getSelfRoleNode",
        "/management/common/getMineInfo",
        "/management/common/checkPwd",
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
        $field = "node_id,pid,name,icon,url,desc,sort,type,is_auth,status";
        $authNode = $this->getAuthNodeFind($where,$field,'node_id desc');
        $authNode = obj2arr($authNode);
        if (in_array($url,$this->commonNode)) return $authNode ?? [];
        if (!$authNode) return $this->r(100,"该功能尚未开放");
        $authNode['d_type'] = 0;
        if ($authNode['status'] == 2) return $this->rFail("该功能已被禁用");
        // 不需要绝对校验
        if ($authNode['is_auth'] == 2) return $authNode;

        $role = $this->getAuthManagerRoleColumn(['manager_id' => $this->manager['manager_id']],'role_id');
        // 查询组织绑定的权限角色
        $or = $this->getAuthOrganizationRoleColumn(['ao_id' => $this->manager['ao_id'],'is_del' => 2],'role_id');
        if (!$role) $role = [];
        if (!$or) $or = [];
        $role = array_unique(array_merge($role,$or));
        if (!$role) return $this->rFail("您暂未被授予权限角色，无法使用系统");

        // 超管全权限免验证
        if ($this->manager['pid'] === 0) return $authNode;

        $roleNode = $this->getAuthRoleNodeFind([["role_id","in",$role],'node_id' => $authNode['node_id'],'is_del' => 2],'rn_id,d_type','d_type desc,rn_id desc');
        if (!$roleNode) return $this->rFail("您无权限操作【" . $authNode['name']. "】");
        $authNode['d_type'] = $roleNode['d_type'];
        return $authNode;
    }


}