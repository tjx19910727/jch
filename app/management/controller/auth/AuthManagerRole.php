<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/19
 * Time: 16:38
 */

namespace app\Management\controller\auth;


use app\management\controller\Common;

class AuthManagerRole extends Common
{
    protected $validatePath = 'app\management\validate\VAuth.';
    /**
     * 查询一条管理员绑定权限的信息
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->authManagerRole->getFind($where);
    }

    /**
     * 获取管理员绑定角色列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData);
        $field = "mr.mr_id,mr.role_id,ar.name,ar.desc,ar.sort,ar.status";
        $result = $this->app->authManagerRole->getList($where,$pageNum,$field,'mr_id desc');
        return $result;
    }

    /**
     * 管理员绑定角色
     * @return array|mixed|string
     */
    public function bind()
    {
        $postData = input();
        try { $this->validate($postData,$this->validatePath . 'AuthManagerRoleAdd');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->authManagerRole->add($postData);
        return $result;
    }

    /**
     * 删除管理员绑定角色关系
     * @return mixed
     */
    public function unbind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        $result = $this->app->authManagerRole->isDel($where);
        return $result;
    }
}