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
        if ($this->manager['pid'] > 0) {
            $where[] = ['role_id',"<>",1];
        }
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
        $postData['ao_id'] = $this->manager['ao_id'];
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
        try {
            $result = $this->app->authRole->del($postData, 0);
            if ($result) {
                $this->app->authRoleNode->updateAuthRoleNode(['is_del' => 1], ['role_id' => $postData['role_id']]);
                $this->app->authRole->updateAuthOrganizationRole(['is_del' => 1], ['role_id' => $postData['role_id']]);
                $this->app->authManagerRole->delAuthManagerRole(['role_id' => $postData['role_id']]);
            }
            return $this->app->authRole->checkTrans($result);
        } catch (\Exception $e) {
            $this->app->authRole->rollbackTrans();
            actionException($e,1);
            return $this->app->authRole->rValidate($e->getMessage());
        }
    }

    /**
     * 复制权限角色
     * @return array|\think\response\Json
     * @throws \Exception
     */
    public function copy()
    {
        $postData = input();
        return $this->app->authRole->copy($postData);
    }
}