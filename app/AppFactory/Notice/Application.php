<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/2
 * Time: 9:31
 */

namespace app\AppFactory\Notice;


use app\AppFactory\Kernel\Providers\Notice\EmailProvider;
use app\AppFactory\Kernel\Providers\Notice\WeChatProvider;
use app\AppFactory\Kernel\ServiceContainer;

/**
 * Class Application
 * @property Email\EmailClient              $email          邮件通知
 * @property WeChat\WeChatClient            $weChat         微信通知
 * @package app\AppFactory\Notice
 */
class Application extends ServiceContainer
{
    protected $providers = [
        EmailProvider::class,
        WeChatProvider::class,
    ];

    /**
     * 一次性触发多个发送平台
     */
    public function send()
    {
        $this->config['sendType'] = 1;
        $this->weChat->send();
        $this->config['sendType'] = 2;
        $this->email->send();
    }
}