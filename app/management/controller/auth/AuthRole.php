<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 9:47
 */

namespace app\management\controller\auth;


use app\management\controller\Common;

class AuthRole extends Common
{

    /**
     * 查询一条权限角色信息
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData,false,['name' => "like"]);
        $field = "role_id,name,desc,sort,status,creator,update_id";
        return $this->app->authRole->getFind($where,$field);
    }

    /**
     * 获取权限角色列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData,false,['name' => "like"]);
        $field = "role_id,`name`,`desc`,`sort`,`status`,creator,update_id";
        $result = $this->app->authRole->getList($where,$pageNum,$field);
        return $result;
    }

    /**
     * 添加权限角色
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try { $this->validate($postData,'app\management\validate\VAuth.AuthRoleAdd');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->authRole->add($postData);
        return $result;
    }

    /**
     * 修改权限角色
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try { $this->validate($postData,'app\management\validate\VAuth.AuthRoleUpdate');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->authRole->update($postData);
        return $result;
    }

    /**
     * 删除权限角色
     * @return mixed
     */
    public function del()
    {
        $postData = input();
        $this->app->authRole->startTrans();
        $flag[] = $this->app->authRole->del($postData['role_id'],0);
        $flag[] = $this->app->authRoleNode->updateAuthRoleNode(['is_del' => 1],['role_id' => $postData['role_id']]);
        $flag[] = $this->app->authManagerRole->updateAuthManagerRole(['is_del' => 1],['role_id' => $postData['role_id']]);
        $result = flag_check($flag);
        return $this->app->authRole->checkTrans($result);
    }
}