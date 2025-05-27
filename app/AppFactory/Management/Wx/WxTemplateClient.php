<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/3
 * Time: 10:10
 */

namespace app\AppFactory\Management\Wx;


use app\AppFactory\Kernel\Traits\Wx\WxTemplateTrait;
use app\AppFactory\Management\ManagementClient;

class WxTemplateClient extends ManagementClient
{
    use WxTemplateTrait;
}