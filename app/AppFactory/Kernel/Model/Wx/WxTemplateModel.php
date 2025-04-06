<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/5/3
 * Time: 9:28
 */

namespace app\AppFactory\Kernel\Model\Wx;


use app\AppFactory\Kernel\Model\BaseModel;

class WxTemplateModel extends BaseModel
{
    protected $pk = "wt_id";
    protected $name = "wx_template";
}