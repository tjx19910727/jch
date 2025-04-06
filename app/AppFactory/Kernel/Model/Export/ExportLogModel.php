<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/7/27
 * Time: 16:46
 */

namespace app\AppFactory\Kernel\Model\Export;


use app\AppFactory\Kernel\Model\BaseModel;

class ExportLogModel extends BaseModel
{
    protected $pk = "export_id";
    protected $name = "export_log";

}