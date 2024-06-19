<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2024/1/9
 * Time: 14:17
 */

namespace app\management\validate\Config;


use app\management\validate\VCommon;

class VConfigSize extends VCommon
{
    protected $rule = [
        "s_id" => "require",
        "label" => "require",
        "length" => "require|number",
        "width" => "require|number",
        "type" => "require|number",
    ];

    protected $message = [
        "s_id.require" => "VConfig.s_id_require",
        "label.require" => "VConfig.label_require",
        "length.require" => "VConfig.length_require",
        "length.number" => "VConfig.length_number",
        "width.require" => "VConfig.width_require",
        "width.number" => "VConfig.width_number",
        "type.require" => "VConfig.type_require",
        "type.number" => "VConfig.type_number",
    ];

    protected $scene = [
        "add" => ["label","length","width","type"],
        "del" => ["s_id"],
    ];

    public function sceneUpdate()
    {
        return $this->only(['s_id','length','width','type'])
            ->append("s_id","require")
            ->remove("length","require")
            ->remove("width","require")
            ->remove("type","require");
    }
}