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
use app\AppFactory\Kernel\Traits\Auth\AuthRoleNodeTrait;
use app\AppFactory\Management\ManagementClient;
use think\response\Json;

class AuthManagerRoleClient extends ManagementClient
{
    use AuthManagerRoleTrait;
    use AuthRoleNodeTrait;
    use AuthNodeTrait;

    protected $commonNode = [
        "/management/common/getSelfRoleNode"
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
        return $authNode ?? [];
        if (in_array($url,$this->commonNode)) return $authNode ?? [];
        if (!$authNode) return $this->rFail("该功能尚未开放");
        $authNode['d_type'] = 0;
        if ($authNode['status'] == 2) return $this->rFail("该功能已被禁用");
        // 不需要绝对校验
        if ($authNode['is_auth'] == 2) return $authNode;

        $role = $this->getAuthManagerRoleColumn(['manager_id' => $this->manager['manager_id']],'role_id');
        if (!$role) return $this->rFail("查无权限角色");
        // 超管全权限免验证
        if ($this->manager['pid'] === 0) return $authNode;

        $roleNode = $this->getAuthRoleNodeFind([["role_id","in",$role],'node_id' => $authNode['node_id'],'is_del' => 2],'rn_id,d_type','rn_id desc');
        if (!$roleNode) return $this->rFail("您无权限执行此操作");
        $authNode['d_type'] = $roleNode['d_type'];
        return $authNode;
    }


}