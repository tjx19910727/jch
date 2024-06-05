<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/6/20
 * Time: 9:46
 */

namespace app\management\controller\auth;


use app\management\controller\Common;

class AuthManager extends Common
{
    /**
     * 查询一条管理员信息
     * @return array|string
     */
    public function getFind()
    {
        $postData = input();
        $where = $this->getWhere($postData);
        return $this->app->authManager->getFind($where);
    }

    /**
     * 获取管理员列表
     * @return mixed
     */
    public function getList()
    {
        $postData = input();
        $pageNum = $postData['pageNum'] ?? 0;
        $where = $this->getWhere($postData,false,['nickname' => "like"]);
        $field = "au.manager_id,au.nickname,au.account,au.pid,au.openid,au.bill_account,au.real_name,au.level,au.sex,au.pic,au.status,au.creator,au.ao_id, au.create_time,ao.organization_name";
        $result = $this->app->authManager->getList($where,$pageNum,$field);
        return $result;
    }

    /**
     * 添加管理员
     * @return array|mixed|string
     */
    public function add()
    {
        $postData = input();
        try { $this->validate($postData,'app\management\validate\VAuth.AuthManagerAdd');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->authManager->add($postData);
        return $result;
    }

    /**
     * 修改管理员
     * @return array|mixed|string
     */
    public function update()
    {
        $postData = input();
        try { $this->validate($postData,'app\management\validate\VAuth.AuthManagerUpdate');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $result = $this->app->authManager->update($postData);
        return $result;
    }

    /**
     * 修改账号密码
     * @return array|mixed|string
     */
    public function updatePassword()
    {
        $postData = input();
        try { $this->validate($postData,'app\management\validate\VAuth.UpdatePassword');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        $update['manager_id'] = $postData['manager_id'];
        $update['password'] = $postData['password'];
        $result = $this->app->authManager->update($update);
        return $result;
    }

    /**
     * 删除管理员
     * @return mixed
     */
    public function del()
    {
        $postData = input();
        $this->app->authManager->startTrans();
        try {
            $result = $this->app->authManager->del(['manager_id' => $postData['manager_id']], 0);
            if ($result) {
                $this->app->authManagerMachine->delAuthManagerMachine(['manager_id' => $postData['manager_id']]);
                $this->app->authManagerRole->delAuthManagerRole(["manager_id" => $postData['manager_id']]);
            }
            return $this->app->authManager->checkTrans($result);
        } catch (\Exception $e) {
            $this->app->authManager->rollbackTrans();
            actionException($e,1);
            return $this->app->authManager->rValidate($e->getMessage());
        }
    }

    /**
     * 工作人员绑定公众号接收微信模板消息通知
     * @return array|bool|string
     */
    public function bindWx()
    {
        $manager_id = input('manager_id');
        if (!$manager_id) return returnState(100,'账号ID不能为空');
        $result = $this->app->authManager->getWxQr($manager_id);
        return $result;
    }
}