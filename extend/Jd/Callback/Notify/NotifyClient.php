<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2022/11/10
 * Time: 15:42
 */

namespace Jd\Callback\Notify;


use Jd\Kernel\BaseClient;
use Jd\Kernel\Traits\CurlTrait;

class NotifyClient extends BaseClient
{
    use CurlTrait;

    /**
     * 回调处理数据
     * @param $message
     * @return string|array
     */
    public function callbackUrl($message)
    {
        if (!$this->validate($message)) {
            return '签名错误';
        }
        return $message['body'];
    }
}