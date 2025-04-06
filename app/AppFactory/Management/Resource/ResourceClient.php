<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/10
 * Time: 14:16
 */

namespace app\AppFactory\Management\Resource;


use app\AppFactory\Kernel\Traits\Resource\ResourceTrait;
use app\AppFactory\Management\ManagementClient;

class ResourceClient extends ManagementClient
{
    use ResourceTrait;
}