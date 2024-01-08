<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2023/8/2
 * Time: 19:33
 */

namespace app\management\controller\auth;


use app\management\controller\Common;

class AuthManagerNotice extends Common
{
    protected $validatePath = "app\management\validate\VAuth.";
    /**
     * 获取账号通知开关列表
     * @return mixed
     */
    public function getList()
    {
        $manager_id = input('manager_id');
        return $this->app->authManagerNotice->getList(['manager_id' => $manager_id],0,'amn_id,manager_id,store_id,notice_type,status','notice_type asc');
    }

    /**
     * 保存账号通知设置
     * @return array|bool|string
     */
    public function saveNoticeSwitch()
    {
        $postData = input();
        $postData = json2arr($postData);
        try { $this->validate($postData,$this->validatePath . 'AuthManagerNotice');} catch (\Exception $e) { return returnValidate($e->getMessage());}
        return $this->app->authManagerNotice->save($postData);
    }
}