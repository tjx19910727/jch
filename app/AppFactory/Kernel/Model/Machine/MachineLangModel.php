<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2025/5/15
 * Time: 15:04
 */

namespace app\AppFactory\Kernel\Model\Machine;


use app\AppFactory\Kernel\Model\BaseModel;

class MachineLangModel extends BaseModel
{
    protected $pk = "ml_id";
    protected $name = "machine_lang";

    protected $schema = [
        "ml_id" => "int",
        "m_id" => "int",
        "machine_id" => "string",
        "logo"  => "string",
        "currency" => "string",
        "desc" => "string",
        "lang" => "string",
        "creator" => "int",
        "create_time" => "int",
        "update_id" => "int",
        "update_time" => "int",
    ];
}