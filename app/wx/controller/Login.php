<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/11/16
 * Time: 9:25
 */

namespace app\wx\controller;


use app\AppFactory\AppFactory;

class Login
{
    /**
     * 扫码登录入口
     */
    public function scanLogin()
    {
        $postData = input();
        AppFactory::wx()->login->scanLogin($postData);
    }

    /**
     * 静默登录回调地址
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Overtrue\Socialite\Exceptions\AuthorizeFailedException
     */
    public function silentCallback()
    {
        $postData = input();
        return AppFactory::wx()->login->silentCallback($postData);
    }

    /**
     * 使用Openid获取账号列表
     * @return array|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUsers()
    {
        $postData = input();
        return AppFactory::wx()->login->getUsersByOpenid($postData);
    }

    /**
     * 选定账号登录
     * @return array|\think\response\Json
     */
    public function managerLogin()
    {
        $postData = input();
        return AppFactory::wx()->login->managerLogin($postData);
    }

}