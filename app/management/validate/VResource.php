<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/10
 * Time: 14:57
 */

namespace app\management\validate;


class VResource extends VCommon
{
    protected $rule = [
        "res_id" => "require",
        "title" => "require|max:100",
        "file_path" => "require|max:255",
        "type" => "require|number",
        "length" => "require|number",
        "width" => "require|number",
        "size" => "require|number",
    ];

    protected $message = [
        "res_id.require" => "VResource.res_id_require",
        "title.require" => "VResource.title_require",
        "title.max" => "VResource.title_max",
        "file_path.require" => "VResource.file_path_require",
        "file_path.max" => "VResource.file_path_max",
        "type.require" => "VResource.type_require",
        "type.number" => "VResource.type_number",
        "length.require" => "VResource.length_require",
        "length.number" => "VResource.length_number",
        "width.require" => "VResource.width_require",
        "width.number" => "VResource.width_number",
        "size.require" => "VResource.size_require",
        "size.number" => "VResource.size_number",
    ];

    protected $scene = [
        "add" => ["title",'file_path','type','size'],
        "del" => ["res_id"],
    ];

    public function sceneUpdate()
    {
        return $this->only(["res_id","title","file_path","type","length","width","size"])
            ->remove("length",'require')
            ->remove("width",'require')
            ->remove("size",'require')
            ->remove("type",'require')
            ->remove("file_path",'require')
            ->remove("title",'require');
    }
}