<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/27
 * Time: 16:49
 */

namespace app\AppFactory\Management\Export;


use app\AppFactory\Kernel\Traits\Export\ExportLogTrait;
use app\AppFactory\Management\ManagementClient;

class ExportLogClient extends ManagementClient
{
    use ExportLogTrait;
}