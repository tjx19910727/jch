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

        $roleNodes = $this->resolveManagerRoleNodes($this->manager, $role, intval($authNode['node_id']));
        if (!$role && !$roleNodes) return $this->rFail("您暂未被授予权限角色或权限模板，无法使用系统");
        if (!$roleNodes) return $this->r(100,"您无权限操作【" . $authNode['name']. "】");
        $authNode['d_type'] = $roleNodes[0]['d_type'];
        $authNode['data_scope'] = $roleNodes[0]['data_scope'] ?? '';
        return $authNode;
    }


}
