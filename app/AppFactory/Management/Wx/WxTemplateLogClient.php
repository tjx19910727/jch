<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/3
 * Time: 10:11
 */

namespace app\AppFactory\Management\Wx;


use app\AppFactory\Kernel\Traits\Wx\WxTemplateLogTrait;
use app\AppFactory\Management\ManagementClient;

class WxTemplateLogClient extends ManagementClient
{
    use WxTemplateLogTrait;
}