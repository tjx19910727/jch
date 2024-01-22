<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/19
 * Time: 15:28
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineGoodsModel extends BaseModel
{
    protected $pk = "mg_id";
    protected $name = "machine_goods";
}