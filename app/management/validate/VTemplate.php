<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/20
 * Time: 15:26
 */

namespace app\management\validate;


class VTemplate extends VCommon
{

    protected $rule = [
        "id" => "require",
        "name" => "require",
        "resolution" => "require",
    ];

    protected $message = [
        "id.require" => "VTemplate.id_require",
        "name.require" => "VTemplate.name_require",
        "resolution.require" => "VTemplate.resolution_require",
    ];

    protected $scene = [
        "add" => ["name", "resolution"],
        "update" => ["id"],
        "del" => ["id"],
    ];
}