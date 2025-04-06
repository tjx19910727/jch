<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/3
 * Time: 9:28
 */

namespace app\AppFactory\Kernel\Model\Wx;


use app\AppFactory\Kernel\Model\BaseModel;

class WxTemplateLogModel extends BaseModel
{
    protected $pk = "wtl_id";
    protected $name = "wx_template_log";
}