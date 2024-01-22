<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/9
 * Time: 14:17
 */

namespace app\management\validate;


class VConfigScene extends VCommon
{
    protected $rule = [
        "id" => "require",
        "name" => "require",
    ];

    protected $message = [
        "id.require" => "VConfigScene.id_require",
        "name.require" => "VConfigScene.name_require",
    ];

    protected $scene = [
        "add" => ["name"],
        "update" => ["id"],
        "del" => ["id"],
    ];
}