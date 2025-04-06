<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 9:47
 */

namespace app\management\controller\auth;


use app\management\controller\Common;

class AuthRoleNode extends Common
{
    protected $validatePath = 'app\management\validate\VAuth.';
    /**
     * 查询一条权限角色绑定权限节点信息
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->authRoleNode->getFind($where);
    }

    /**
     * 获取权限角色绑定权限节点列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData);
        $field = "rn_id,role_id,rn.node_id,pid,an.name node_name,icon,type node_type,d_type";
        $result = $this->app->authRoleNode->getList($where,$pageNum,$field);
        return $result;
    }

    /**
     * 权限角色绑定与取消绑定权限节点
     * @return array|string
     * @throws \Exception
     */
    public function bind()
    {
        $postData = input();
        $postData = json2arr($postData);
        try { $this->validate($postData,$this->validatePath . 'AuthRoleNodeAdd');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->authRoleNode->bind($postData);
    }

    /**
     * 解除权限角色绑定权限节点关系
     * @return mixed
     */
    public function unbind()
    {
        return returnState(100,'此接口已作废');
        $postData = input();
        $where = $this->getWhere($postData);
        $result = $this->app->authRoleNode->isDel($where);
        return $result;
    }
}