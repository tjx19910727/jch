<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 14:45
 */

namespace app\AppFactory\Management\Template;


use app\AppFactory\Kernel\Traits\Template\TemplateTrait;
use app\AppFactory\Management\ManagementClient;

class TemplateClient extends ManagementClient
{
    use TemplateTrait;
}