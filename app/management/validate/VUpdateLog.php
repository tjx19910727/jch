<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/3/1
 * Time: 17:21
 */

namespace app\management\validate;


class VUpdateLog extends VCommon
{
    protected $rule = [
        "id" => "require",
        "title" => "require|max:255",
        "content" => "require"
    ];

    protected $message = [
        "id.require" => "ID不能为空",
        "title.require" => "标题不能为空",
        "title.max" => "标题长度超限制",
        "content.require" => "更新内容不能为空",
    ];

    protected $scene = [
        "add" => ['title',"content"],
        "update" => ["id",'title',"content"],
        "del" => ['id'],
    ];
}