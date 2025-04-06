<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/11/11
 * Time: 11:33
 */

namespace app\management\validate\Action;


use app\management\validate\VCommon;

class VActionVideo extends VCommon
{
    protected $rule = [
        "id" => "require",
        "video_name" => "require",
        "path" => "require",
    ];

    protected $message = [
        "id.require" => "id_require",
        "video_name.require" => "video_name_require",
        "path.require" => "path_require",
    ];
    protected $scene = [
        "add" => ["video_name","path"],
        "update" => ["id"],
        "del" => ["id"],
    ];
}