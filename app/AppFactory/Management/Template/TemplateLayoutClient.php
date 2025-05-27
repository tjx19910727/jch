<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 14:45
 */

namespace app\AppFactory\Management\Template;


use app\AppFactory\Kernel\Traits\Template\TemplateLayoutTrait;
use app\AppFactory\Management\ManagementClient;

class TemplateLayoutClient extends ManagementClient
{
    use TemplateLayoutTrait;
}