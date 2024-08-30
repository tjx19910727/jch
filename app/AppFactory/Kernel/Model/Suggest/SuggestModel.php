<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/8/17
 * Time: 9:55
 */

namespace app\AppFactory\Kernel\Model\Suggest;


use app\AppFactory\Kernel\Model\BaseModel;

class SuggestModel extends BaseModel
{
    protected $pk = "s_id";
    protected $name = "suggest";
}